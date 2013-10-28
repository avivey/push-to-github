<?php

class GithubApiCallFuture extends FutureProxy {
  const BASE_URI = 'https://api.github.com/' ; // todo get this from provider.
  const GITHUB_PROVIDER_KEY = 'github:github.com';

  private $uri;
  private $future;

  public function __construct($api, PhabricatorUser $user) {
    // parent::__construct();


    $account = id(new PhabricatorExternalAccountQuery())
      ->setViewer($user)
      ->withUserPHIDs(array($user->getPHID()))
      ->withAccountDomains(array("github.com")) // todo this too
      ->executeOne();

    $github_provider = PhabricatorAuthProvider::getEnabledProviderByKey(
      self::GITHUB_PROVIDER_KEY); // todo get this from somewhere.
    $access_token = $github_provider->getOAuthAccessToken($account);

    $this->uri = new PhutilURI(self::BASE_URI . $api);
    $this->uri->setQueryParam('access_token', $access_token);
    $this->future = new HTTPSFuture($this->uri);
    $this->setProxiedFuture($this->future);
  }

  protected function didReceiveResult($result){
    return $result;
  }

  public function setMethod($method) {
    $this->future->setMethod($method);
  }
  public function setData($encoded_data) {
    $future->setData($encoded_data);
  }
}
