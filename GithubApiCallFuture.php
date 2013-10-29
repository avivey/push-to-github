<?php

class GithubApiCallFuture extends FutureProxy {
  const BASE_URI = 'https://api.github.com/' ; // todo get this from provider.
  const GITHUB_PROVIDER_KEY = 'github:github.com';

  private $uri;
  private $future;

  public static function getAccessToken(PhabricatorUser $user) {
    $account = id(new PhabricatorExternalAccountQuery())
      ->setViewer($user)
      ->withUserPHIDs(array($user->getPHID()))
      ->withAccountDomains(array("github.com")) // todo this too
      ->executeOne();

    $github_provider = PhabricatorAuthProvider::getEnabledProviderByKey(
      self::GITHUB_PROVIDER_KEY); // todo get this from somewhere.
    return $github_provider->getOAuthAccessToken($account);
  }

  public function __construct($api, $access_token, array $data = null) {
    $this->uri = new PhutilURI(self::BASE_URI . $api);
    $this->uri->setQueryParam('access_token', $access_token);
    $this->future = new HTTPSFuture($this->uri);
    $this->setProxiedFuture($this->future);

    if ($data !== null) {
      $this->setData(json_encode($data));
    }
  }

  protected function didReceiveResult($result){
    return $result;
  }

  public function setMethod($method) {
    $this->future->setMethod($method);
  }
  public function setData($encoded_data) {
    $this->future->setData($encoded_data);
  }
}
