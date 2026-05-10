<?php
// Accepts optional $pageTitle variable from the including page
$pageTitle = $pageTitle ?? 'HealthKiosk';
?>
<header class="header">
  <div class="logo">
    <div class="logo-icon">🏥</div>
    <div class="logo-name">HealthKiosk</div>
  </div>
  <div class="clock-block">
    <div class="clock" id="clock">--:--</div>
    <div class="clock-date" id="date">---</div>
    <div id="headerStep" class="header-step"></div>
  </div>
</header>
