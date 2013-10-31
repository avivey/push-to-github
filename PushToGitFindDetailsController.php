<?php

class PushToGitFindDetailsController extends AvivUtilController {
   public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $pwd = '/tmp/phwork/tst__workspace/';

    $future = new ExecFuture(
      '/bin/bash -c %s',
      'comm -23 <(git ls-tree -r HEAD |sort)  <(git ls-tree -r HEAD~ |sort)');
    $future->setCwd($pwd);

    list($err, $stdout, $stderr) = $future->resolve();
    $dd = array('exit'=>$err, 'out'=>$stdout, 'err'=>$stderr);
    $parsed = $this->parseGitTree($stdout);
    $dd['parsed'] = $parsed;

    $new_blobs = array();
    foreach ($parsed as $data) {
      if ($data['type'] === 'blob') {
        $new_blobs[$data['sha']] = $data;
      }
    }

    $access_token = GithubApiCallFuture::getAccessToken($viewer);
    $dd['token'] = $access_token;
    $futures = array();
    foreach ($new_blobs as $blob) {
      $content = file_get_contents($pwd.$blob['path']);
      $content = base64_encode($content);
      $future =
       self::makePushBlobFuture('avivey/test-repo', $content, $access_token);
      $futures[$blob['sha']] = $future;
    }

    $repo = 'avivey/test-repo';

    $parsed = array();
    foreach ($futures as $hash => $future) {
      $res = self::parseGithubApiFuture($future);
      $res['WIN'] = idx($res['body'], 'sha') == $hash;
      $parsed[$hash] = $res;
    }
    $dd['creat blobs call'] = $parsed;

    $future = new ExecFuture(
      '/bin/bash -c %s',
      'git cat-file commit HEAD');
    $future->setCwd($pwd);
    $commit_info = $future->resolvex();
    $commit_info = $commit_info[0];

    $commit_info = $this->parseCommit($commit_info);
    $dd['wanted tree hash'] = $commit_info;

    $future = new ExecFuture(
      '/bin/bash -c %s',
      'git ls-tree -r HEAD');
    $future->setCwd($pwd);
    $head_tree = $future->resolvex();
    $head_tree = $this->parseGitTree($head_tree[0]);
    $dd['tree'] = json_encode($head_tree);

    $future = $this->makeCreateTreeFuture('avivey/test-repo', $head_tree, $access_token);
    $dd['create tree call'] = self::parseGithubApiFuture($future);

    $future = $this->makeCommitFuture(
      'avivey/test-repo',
      $commit_info['message'],
      $commit_info['tree'],
      $commit_info['parent'], // TODO add author data.
      $access_token
      );
    $commit = self::parseGithubApiFuture($future);
    $dd['create make commit'] = $commit;
    $commit = idx($commit, 'body');
    $commit = idx($commit, 'sha');

    if ($commit) {
      $future = $this->makeUpdateMasterFuture($repo, $commit, $access_token);
      $dd['update'] = self::parseGithubApiFuture($future);
    }
    return $this->buildHumanReadableResponse($dd);
  }

  function makeCommitFuture($repo, $message, $tree, $parent, $access_token) {
    // todo add committer data
    // todo get information from phabricator commit object.
    $data = array(
      'message' => $message,
      'tree' => $tree,
      'parents' => array($parent),
      // todo parse author info + time.
      'author' => array(
        'name' => 'Aviv Eyal',
        'email' => 'avivey@gmail.com',
        'date' => '2013-10-27T17:10:40-0700',
      ),
    );
    $future = new GithubApiCallFuture(
      "repos/$repo/git/commits",
      $access_token,
      $data);
    $future->setMethod('POST');

    return $future->start();
  }

  function makeUpdateMasterFuture($repo, $commit, $access_token) {
    $data = array('sha' => $commit);

    $future = new GithubApiCallFuture(
      "repos/$repo/git/refs/heads/master",
      $access_token,
      $data);
    $future->setMethod('PATCH');
    return $future->start();
  }

  function makeCreateTreeFuture($repo, $tree, $access_token) {
    $data = array('tree' => $tree);

    $future = new GithubApiCallFuture(
      "repos/$repo/git/trees",
      $access_token,
      $data);
    $future->setMethod('POST');
    return $future->start();
  }

  function makePushBlobFuture($repo, $blob_in_base64, $access_token) {
    $data = array(
      'encoding' => 'base64',
      'content' => $blob_in_base64,
    );
    $future = new GithubApiCallFuture(
      "repos/$repo/git/blobs",
      $access_token,
      $data);
    $future->setMethod('POST');
    $future->start();
    return $future;
  }

  function parseGithubApiFuture($future) {
    $dd = array();
    // $dd['uri'] = ''.$future->uri;
    list($status, $body, $headers) = $future->resolve();

    $dd['api status'] = $status->getStatusCode();
    // $dd['headers'] = $headers;
    $dd['body'] = json_decode($body, true);
    return $dd;
  }

  private function parseGitTree($stdout) {
    $result = array();

    $stdout = trim($stdout);
    if (!strlen($stdout)) {
      return $result;
    }

    $lines = explode("\n", $stdout);
    foreach ($lines as $line) {
      $matches = array();
      $ok = preg_match(
        '/^(\d{6}) (blob|tree|commit) ([a-z0-9]{40})[\t](.*)$/',
        $line,
        $matches);
      if (!$ok) {
        throw new Exception("Failed to parse git ls-tree output!");
      }
      // this now matches github's api.
      $result[] = array(
        'mode' => $matches[1],
        'type' => $matches[2],
        'sha'  => $matches[3],
        'path' => $matches[4],
      );
    }
    return $result;
  }

  function parseCommit($commit) {
    $commit = trim($commit); // I hope.
    $text = explode("\n", $commit);
    $data = array();
    $message = array();
    $in_message = false;
    while(!empty($text)) {
      $line = array_shift($text);
      if ($in_message) {
        array_push($message, $line);
      } else if (empty($line)) {
        $in_message = true;
      } else {
        $line = explode(' ', $line, 2);
        $data[$line[0]] = $line[1];
      }
    }
    $data['message'] = implode("\n", $message);
    return $data;
  }
}