<?php
// reload-failed-body.php
// variables: $student_name, $student_email, $coins_cost, $reason
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reload Request Failed</title>
</head>
<body style="margin:0; padding:0; background:#f2f2f2;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding:20px;">
            <!--[if mso]>
            <table width="600" cellpadding="0" cellspacing="0"><tr><td>
            <![endif]-->
            <table width="100%" max-width="600" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff; border-radius:4px; overflow:hidden;">
                <tr>
                    <td style="padding:24px; font-family:Arial, sans-serif; color:#333333; line-height:1.5;">

                        <h2 style="margin-top:0; margin-bottom:4px; font-size:20px; color:#333333;">
                            Hi <?php echo esc_html($student_name); ?>,
                        </h2>

                        <p style="margin:0 0 4px;">
                            ඔබගේ <strong>Reload redeem අයදුම</strong> <strong style="color:#c0392b;">ප්‍රතික්ෂේප</strong> වී ඇත</strong>.
                        </p>

                        <?php if ( $reason ) : ?>
                            <p style="margin:0 0 4px; background:#fdecea; padding:12px; border-left:4px solid #e74c3c;">
                                <strong>සටහන:</strong> <?php echo esc_html($reason); ?>
                            </p>
                        <?php endif; ?>

                        <p style="margin:0 0 2px;">
                            ඔබගෙන් අයකරගත් <strong>Coins <?php echo intval( $coins_cost ); ?>ක</strong> ප්‍රමාණයද නැවත ඔබගේ ගිණුමට බැර කර ඇත.
                        </p>

                        <p style="margin:0 0 4px;">
                            වැඩිදුර විස්තර හෝ සහාය සඳහා, <a href="mailto:contact@differently.study" style="color:#2980b9; text-decoration:none;">contact@differently.study</a> හරහා අප සමඟ සම්බන්ධ වන්න.
                        </p>

                        <p style="margin:0;">
                            මෙයට,
                            <strong>Differently කණ්ඩායම.</strong>
                        </p>

                    </td>
                </tr>
            </table>
            <!--[if mso]>
            </td></tr></table>
            <![endif]-->
        </td>
    </tr>
</table>
</body>
</html>