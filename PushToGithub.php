<?php

class PushToGitHubApp extends PhabricatorApplication {
  public function getBaseURI() {
    return '/magic/';
  }

  public function getRoutes() {
    return array(
      '/magic/' => 'LandRevisionController',
      '/local-magic/' => 'LandLocally',
      '/big-magic/' => 'PushToGitFindDetailsController',
    );
  }

  public function getShortDescription() {
    return "magick";
  }

  public function getIconName() {
    return 'github';
  }

 public function getApplicationGroup() {
    return self::GROUP_CORE;
  }
}
