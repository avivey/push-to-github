<?php

abstract class AvivUtilController extends PhabricatorController{

  function build($result) {
    return $this->buildHumanReadableResponse($result);
  }

  function buildHumanReadableResponse(
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

    $result_head = id(new PHUIHeaderView())
      ->setHeader(pht('demagicking'));


    return $this->buildApplicationPage(
      array(
        $result_head,
        $result_table,
      ),
      array(
        'title' => 'magic',
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
