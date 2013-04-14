<?php

function unsolclic_todo($dev) {
  $version = "vX.XX-TODO";

  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  _outln_comment();
  _outln_comment(t("This firmware configuration is under construction or not yet started development."));
  _outln_comment(t("If you want to collaborate and contribute with code to make it work,"));
  _outln_comment(t("please subscribre to our development lists at:"));
  _outln_comment("https://llistes.guifi.net/sympa/info/guifi-dev");
  _outln_comment(t("The source for this application can be downloaded from the GIT repository:"));
  _outln_comment(t("https://gitorious.org/guifi/drupal-guifi"));
  _outln_comment(t("Contributions are always welcome!"));
  _outln_comment();
}

?>
