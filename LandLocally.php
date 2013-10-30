<?php

class LandLocally  extends AvivUtilController {
  public function processRequest() {
    $request = $this->getRequest();
    $user = $request->getUser();
    $dd = array();


    $call = new ConduitCall(
      'differential.getrawdiff',
      array(
        'diffID'        => 27,
      ));

    $call->setUser($user);
    $result = $call->execute();

    $dd['res'] = $result;

    $repo = id(new PhabricatorRepository())->
      loadOneWhere('callsign = %s', 'TST');
    // $dd['repo'] = $repo->to;
    $dd['path'] = $this->getCleanWorkspace($repo);

    return $this->buildHumanReadableResponse($dd);
  }

  function getCleanWorkspace(PhabricatorRepository $repo) {
    $path = $repo->getLocalPath();

    $path = rtrim($path, '/');
    $path = $path . '__workspace/';

    // todo clone.

    $working_copy = new ArcanistGitAPI($path);
    $working_copy->execxLocal('clean -fd');
    $working_copy->execxLocal('checkout master');
    $working_copy->execxLocal('fetch');
    $path = $working_copy->execxLocal('reset --hard origin/master');

    return $path;
  }

  public function loadFileByPHID($phid) {
    $file = id(new PhabricatorFile())->loadOneWhere(
      'phid = %s',
      $phid);
    if (!$file) {
      return null;
    }
    return $file->loadFileData();
  }

}