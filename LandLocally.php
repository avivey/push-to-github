<?php

class LandLocally  extends AvivUtilController {
  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();
    $dd = array();

    $diff_id = 27;
    $call = new ConduitCall(
      'differential.getrawdiff',
      array(
        'diffID'   => $diff_id,
      ));

    $call->setUser($viewer);
    $raw_diff = $call->execute();

    $dd['raw_diff'] = $raw_diff;
    $tmp_file = new TempFile();
    Filesystem::writeFile($tmp_file, $raw_diff);
    $dd['file'] = $tmp_file ."";

    $repo = id(new PhabricatorRepository())->
      loadOneWhere('callsign = %s', 'TST');
    $workspace = $this->getCleanWorkspace($repo);
    // $dd['path'] = $workspace;

    $workspace->execxLocal('apply --index %s', $tmp_file);
    $dd['status'] = $workspace->execxLocal('status');
    $dd['status'] = $dd['status'][0];

    $workspace->reloadWorkingCopy();

    $diff = id(new DifferentialDiffQuery())
      ->withIDs(array($diff_id))
      ->setViewer($viewer)
      ->executeOne();

    $revision = $diff->getRevision();
    $dd['revision'] = $revision==null? 'null':$revision->getID();

    $call = new ConduitCall(
      'differential.getcommitmessage',
      array(
        'revision_id'   => $revision->getID(),
      ));

    $call->setUser($viewer);
    $message = $call->execute();
    // $dd['message'] = $message;

    $workspace->execxLocal(
      '-c user.name=%s -c user.email=%s  commit -m %s',
      'aviv e',
      'aviv@mail.com',
      $message
      );

    $dd['log'] = $workspace->execxLocal('log -1');
    $dd['log'] = $dd['log'][0];

    return $this->buildHumanReadableResponse($dd);
  }

  function getCleanWorkspace(PhabricatorRepository $repo) {
    $path = $repo->getLocalPath();

    $path = rtrim($path, '/');
    $path = $path . '__workspace/';

    // todo clone.

    $workspace = new ArcanistGitAPI($path);
    $workspace->execxLocal('clean -fd');
    $workspace->execxLocal('checkout master');
    $workspace->execxLocal('fetch');
    $workspace->execxLocal('reset --hard origin/master');
    $workspace->reloadWorkingCopy();

    return $workspace;
  }

  function getDiffBundle() {
    $diff = new DifferentialDiff();
    $diff->attachChangesets($generated_changesets);
    $diff_dict = $diff->getDiffDict();

    $changes = array();
    foreach ($diff_dict['changes'] as $changedict) {
      $changes[] = ArcanistDiffChange::newFromDictionary($changedict);
    }
    $bundle = ArcanistBundle::newFromChanges($changes);

    $bundle->setLoadFileDataCallback(array($this, 'loadFileByPHID'));
    return $bundle;
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