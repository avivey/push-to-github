<?php

class PushToGitHubController extends PhabricatorController {

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

  private function buildHumanReadableResponse(
    $result) {

    $param_rows = array();

    $param_table = new AphrontTableView($param_rows);
    $param_table->setDeviceReadyTable(true);
    $param_table->setColumnClasses(
      array(
        'header',
        'wide',
      ));

    $result_rows = array();
    foreach ($result as $key => $value) {
      $result_rows[] = array(
        $key,
        $this->renderAPIValue($value),
      );
    }

    $result_table = new AphrontTableView($result_rows);
    $result_table->setDeviceReadyTable(true);
    $result_table->setColumnClasses(
      array(
        'header',
        'wide',
      ));

    $param_panel = new AphrontPanelView();
    $param_panel->setHeader('Method Parameters');
    $param_panel->appendChild($param_table);

    $result_panel = new AphrontPanelView();
    $result_panel->setHeader('Method Result');
    $result_panel->appendChild($result_table);

    $param_head = id(new PHUIHeaderView())
      ->setHeader(pht('Method Parameters'));

    $result_head = id(new PHUIHeaderView())
      ->setHeader(pht('Method Result'));


    return $this->buildApplicationPage(
      array(
        // $crumbs,
        // $param_head,
        // $param_table,
        $result_head,
        $result_table,
      ),
      array(
        'title' => 'Method Call Result',
        'device' => true,
      ));
  }
  function renderAPIValue($value) {
    $json = new PhutilJSON();
    if (is_array($value)) {
      $value = $json->encodeFormatted($value);
    }

    $value = hsprintf('<pre style="white-space: pre-wrap;">%s</pre>', $value);

    return $value;
  }

}

class PushToGitHubApp extends PhabricatorApplication {
  public function getBaseURI() {
    return '/magic/';
  }

  public function getRoutes() {
    return array(
      '/magic/' => 'PushToGitFindDetailsController',
      '/big-magic/' => 'PushToGitHubController',
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
