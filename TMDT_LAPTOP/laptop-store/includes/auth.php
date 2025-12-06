<?php
// Authentication helpers
function isLoggedIn(){
  return !empty($_SESSION['user_id']);
}
?>
