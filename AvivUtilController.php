<?php

abstract class AvivUtilController extends PhabricatorController{

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
