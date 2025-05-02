<?php
// Subject for “export summary” email.
// $timestamp   (string)— human‐readable date/time
return sprintf(
  /* translators: %1$s = export date/time, e.g. “2025-04-29 17:00” */
  __( 'Daily Reload Redeem Requests Summary %s!', 'points-plus' ),
  $timestamp
);
