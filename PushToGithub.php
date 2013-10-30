<?php

class PushToGitHubController extends AvivUtilController {

  const GITHUB_PROVIDER_KEY = 'github:github.com';
  const TEST_REPO_GIT_ID = 13850529;

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();
    $panel = id(new AphrontPanelView())
      ->setHeader("Header");


    $github_provider = PhabricatorAuthProvider::getEnabledProviderByKey(self::GITHUB_PROVIDER_KEY);

    $account = id(new PhabricatorExternalAccountQuery())
      ->setViewer($viewer)
      ->withUserPHIDs(array($viewer->getPHID()))
      ->withAccountDomains(array("github.com"))
      ->executeOne();

  //  $account = idx($accounts, $p);
    $access_token = $github_provider->getOAuthAccessToken($account  );
    // $panel->appendChild($access_token);

    $github_user = 'avivey';
    $repo = self::TEST_REPO_GIT_ID;
    $repo = 'test-repo';
    $blob_sha = '46b67d951d1094069a171385184f49340088579d';

    $new_blob_created = "e31d47b45048e0a1b66326cb46c9643cd04b5b84";

    $first_tree_sha = '476a54eee64147046209c323ad82acf3b4bfaa75';
    $new_tree = '7611f602575ea17aa782ae8aa55ae78009306527';
    $new_commit_sha = 'a6faa74a475e0567962c94b041cf1481a3b99a41';
    $encoded_data = json_encode(
      array(
        'sha'=> $new_commit_sha
        )
      );

    $uri = new PhutilURI("https://api.github.com/repos/$github_user/$repo/git/refs/heads/master");
    $uri->setQueryParam('access_token', $access_token);
    $future = new HTTPSFuture($uri);
    $future->setMethod('PATCH');
    $future->setData($encoded_data);

    // NOTE: GitHub requires a User-Agent string.
    $future->addHeader('User-Agent', 'PhutilAuthAdapterOAuthGitHub');

    list($status, $body, $headers)  = $future->resolve();
    // var_dump($status);
    // var_dump($headers);
    $dd = json_decode($body, true);

    // var_dump(base64_decode($dd['content']));

    return $this->buildHumanReadableResponse(array($headers, 'result'=>$dd));
  }
}

class PushToGitHubApp extends PhabricatorApplication {
  public function getBaseURI() {
    return '/magic/';
  }

  public function getRoutes() {
    return array(
      '/magic/' => 'LandLocally',
      '/big-magic/' => 'PushToGitFindDetailsController',
      '/old-magic/' => 'PushToGitHubController',
    );
  }

  public function getShortDescription() {
    return "magick";
  }

  public function getIconName() {
    return 'harbormaster';
  }
 public function getApplicationGroup() {
    return self::GROUP_CORE;
  }
}
