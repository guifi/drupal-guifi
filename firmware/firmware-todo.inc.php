<?php

function unsolclic_todo($dev) {
  $version = "vX.XX-TODO";

  _outln_comment();
  _outln_comment('unsolclic version: '.$version);
  _outln_comment();
  _outln_comment(t("This firmware is under construction or not yet started development."));
  _outln_comment(t("If you want to collaborate and contribute with code to make it work,"));
  _outln_comment(t("please join to our development lists at:"));
  _outln_comment(t("(guifi-rdes@llistes.projectes.lafarga.org) and visit our website and learn"));
  _outln_comment(t("how to use the module subversion. (http://guifi.net/ca/svn)."));
  _outln_comment();

}

?>