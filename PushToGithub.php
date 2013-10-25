<?php

class PushToGitHubController extends PhabricatorController {
  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();
    $panel = id(new AphrontPanelView())
      ->setHeader("Header");

    $p='github:github.com';

    $providers = PhabricatorAuthProvider::getAllProviders();
    $account = id(new PhabricatorExternalAccountQuery())
      ->setViewer($viewer)
      ->withUserPHIDs(array($viewer->getPHID()))
      ->withAccountDomains(array("github.com"))
      ->executeOne();


    $github_provider = idx($providers, $p);
  //  $account = idx($accounts, $p);
    $access_token = $github_provider->getOAuthAccessToken($account  );
    // $panel->appendChild($access_token);

    $uri = new PhutilURI('https://api.github.com/rate_limit');
    $uri->setQueryParam('access_token', $access_token);
    $future = new HTTPSFuture($uri);

    // NOTE: GitHub requires a User-Agent string.
    $future->addHeader('User-Agent', 'PhutilAuthAdapterOAuthGitHub');

    list($body) = $future->resolvex();

    $panel->appendChild($body);

    return $this->buildApplicationPage(
      $panel,
      array(      )
      );
  }

}

class PushToGitHubApp extends PhabricatorApplication {
  public function getBaseURI() {
    return '/magic/';
  }

  public function getRoutes() {
    return array(
      '/magic/' => 'PushToGitHubController',
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
