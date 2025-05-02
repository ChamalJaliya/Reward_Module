<?php
// Body template for “reload completed” email.
// variables available: $student_name, $student_email, $reload_value
?>
<html>
<head>
  <style>
    body { font-family: sans-serif; }
    p { margin: 0 0 2px; }
  </style>
</head>
<body>
  <p>Hi <?php echo esc_html( $student_name ); ?>,</p>
  <p>
    ඔබට උණුසුම් සුබ පැතුම්!</p>
    ඔබ ඉදිරිපත් කළ Reload redeem අයදුම අප විසින් සලකා බැලූ අතර, <strong>රු. <?php echo intval( $reload_value ); ?></strong> ක Reload මුදල ඔබගේ දුරකථන අංකයට සාර්ථකව බැර කර ඇත.
  <p>Differently සමග දිගටම රැදී සිටිමින්, ඔබගේ දැනුම වර්ධනය කරගනිමින් තවත් ජයග්‍රහණ අත් කරගන්න.</p>
  <p>මෙයට,</p>
  <p>Differently කණ්ඩායම.</p>
</body>
</html>