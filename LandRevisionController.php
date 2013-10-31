<?php

class LandRevisionController extends AvivUtilController {

  const GITHUB_PROVIDER_KEY = 'github:github.com';
  const TEST_REPO_GIT_ID = 13850529;

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $revision_id = $request->getInt('revisionID');
    $github_repo = $request->getStr('githubRepo');

    if ($revision_id != null && strlen($github_repo)) {

      $log = id(new LandLocally($request))
        ->landRevisionLocally($revision_id, $viewer);

      if (idx($log, 'landed locally') !== true) {
        return $this->build($log);
      }

      $workdir = $log['workdir'];
      $log2 = id(new PushCommitToGithub($request))
        ->pushLastCommit($workdir, $github_repo, $viewer);

      return $this->build($log + $log2);
    }

    $panel = id(new AphrontPanelView())
      ->setHeader("Landing to GH demo");

    $form = id(new AphrontFormView())
      ->setUser($viewer);

    $form->appendChild(id(new AphrontFormTextControl())
      ->setName('revisionID')
      ->setLabel('revision ID')
      );

    $form->appendChild(id(new AphrontFormTextControl())
      ->setName('githubRepo')
      ->setLabel('Github Repo')
      ->setValue('avivey/test-repo')
      ->setCaption('should be available from the repo')
      );

    $form->appendChild(
        id(new AphrontFormSubmitControl())
          ->setValue('Submit'));
    $panel->appendChild($form);

    return $this->buildApplicationPage(
      $panel,
      array(
        'title'   => 'UI Example',
        'device'  => true,
      ));
    }
}

