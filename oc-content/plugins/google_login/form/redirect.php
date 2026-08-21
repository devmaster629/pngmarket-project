<?php
  // INIT hook should login user when gglLogin=1
  // $gglLogin = Params::getParam('gglLogin');

  ggl_callback(true);

  header('Location:' . osc_base_url());
  exit;
?>