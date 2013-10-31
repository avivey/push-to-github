<?php

class LandLocally  extends AvivUtilController {
  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();
    $dd = array();

    $revision_id = $request->getInt('revisionID');
    if (!strlen($revision_id)) {
      $dd['error'] = 'need param revisionID';
      return $this->build($dd);
    }

    return $this->build(
      $this->landRevisionLocally($revision_id, $viewer));
    }

  public function landRevisionLocally($revision_id, $viewer) {
    $revision = id(new DifferentialRevisionQuery())
      ->withIDs(array($revision_id))
      ->setViewer($viewer)
      ->executeOne();
    if (!$revision) {
      return array('error'=> "revision $revision_id not found");
    }
    $dd = array();

    $diff = $revision->loadActiveDiff();
    $diff_id = $diff->getID();

    $call = new ConduitCall(
      'differential.getrawdiff',
      array(
        'diffID'   => $diff_id,
      ));

    $call->setUser($viewer);
    $raw_diff = $call->execute();

    $dd['raw_diff'] = strlen($raw_diff) > 500? strlen($raw_diff).' chars' : $raw_diff;
    $tmp_file = new TempFile();
    Filesystem::writeFile($tmp_file, $raw_diff);

    $repo = id(new PhabricatorRepository())->
      loadOneWhere('callsign = %s', 'TST');
    $workspace = $this->getCleanWorkspace($repo);
    $dd['workdir'] = $workspace->getPath();

    try {
      $workspace->execxLocal('apply --index %s', $tmp_file);
    } catch (CommandException $ex) {
      $dd['apply exception'] = $ex->getMessage();
    }
    $dd['status'] = $workspace->execxLocal('status');
    $dd['status'] = $dd['status'][0];

    $workspace->reloadWorkingCopy();

    $call = new ConduitCall(
      'differential.getcommitmessage',
      array(
        'revision_id'   => $revision->getID(),
      ));

    $call->setUser($viewer);
    $message = $call->execute();
    // $dd['message'] = $message;

    $author = id(new PhabricatorUser())->loadOneWhere(
      'phid = %s',
      $revision->getAuthorPHID());


    $author_string = $author->getRealName().' <'.$author->loadPrimaryEmailAddress().'>';
    try {
      $workspace->execxLocal(
        '-c user.name=%s -c user.email=%s commit -m %s --author=%s',
        // -c will set the 'commiter'
        $viewer->getRealName(),
        $viewer->loadPrimaryEmailAddress(),
        $message,
        $author_string);
    } catch (CommandException $ex) {
      $dd['commit exception'] = $ex->getMessage();
    }

    $dd['log'] = $workspace->execxLocal('log -1 --format=fuller');
    $dd['log'] = $dd['log'][0];

    $dd['landed locally'] = true;

    return $dd;
  }

  function getCleanWorkspace(PhabricatorRepository $repo) {
    $path = $repo->getLocalPath();

    $path = rtrim($path, '/');
    $path = $path . '__workspace';

    // todo clone.

    $workspace = new ArcanistGitAPI($path);
    $workspace->execxLocal('clean -fd');
    $workspace->execxLocal('checkout master');
    $workspace->execxLocal('fetch');
    $workspace->execxLocal('reset --hard origin/master');
    $workspace->reloadWorkingCopy();

    return $workspace;
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