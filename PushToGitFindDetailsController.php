<?php

class PushToGitFindDetailsController extends AvivUtilController {
   public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $pwd = '/home/avive/code/test-repo';

    $api = new GithubApiCallFuture('', $viewer);
    $api->start();

    $future = new ExecFuture(
      '/bin/bash -c %s',
      'comm -23 <(git ls-tree -r HEAD |sort)  <(git ls-tree -r HEAD~ |sort)');
    $future->setCwd($pwd);

    list($err, $stdout, $stderr) = $future->resolve();
    $dd = array('exit'=>$err, 'out'=>$stdout, 'err'=>$stderr);
    $parsed = $this->parseGitTree($stdout);
    $dd['parsed'] = $parsed;

    $new_blobs = array();
    foreach ($parsed as $path => $data) {
      if ($data['type'] === 'blob') {
        $new_blobs[$path] = $data;
      }
    }

    return $this->buildHumanReadableResponse($dd);
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
      $result[$matches[4]] = array(
        'mode' => $matches[1],
        'type' => $matches[2],
        'ref'  => $matches[3],
      );
    }
    return $result;
  }
}