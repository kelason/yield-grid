<?php
/** @var array<string, mixed> $mail */
$e = fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
</head>
<body style="font-family: Helvetica, Arial, sans-serif; color: #1c1917; font-size: 14px; line-height: 1.6;">
<p style="font-size: 18px; font-weight: bold;"><?= $e($mail['title']) ?></p>
<?php foreach ($mail['lines'] as $line) { ?>
<p><?= $e($line) ?></p>
<?php } ?>
<p><a href="<?= $e($mail['cta_url']) ?>" style="display: inline-block; background: #4a8c42; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 8px;"><?= $e($mail['cta_label']) ?></a></p>
<p style="color: #78716c; font-size: 12px;">YieldGrid tracks your side of the process — PCIC and your MAO make all coverage decisions. Confirm program details with them.</p>
</body>
</html>
