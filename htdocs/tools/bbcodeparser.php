<?php
require_once('BBCodeParser2/HTML/BBCodeParser2.php');
require_once 'configuratie.include.php';

if (!LoginModel::mag('P_LEDEN_READ') OR ! LoginModel::mag('P_OUDLEDEN_READ')) {
	echo 'Niet voldoende rechten';
	exit;
}

/* get options from the ini file */
$config = parse_ini_file(LIB_PATH . 'BBCodeParser2/HTML/CSRBBCodeSettings.ini', true);
$options = $config['toolsbbcodeparser'];

/* do yer stuff! */
$parser = new HTML_BBCodeParser2($options);
$parser->setText($_GET['string']);
$parser->parse();
$parsed = $parser->getParsed();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>HTML_BBCodeParser2 (by Stijn de Reede)</title>
	</head>
	<body>
		<form method='get' action='bbcodeparser.php'>
			<table border='1' cellpadding='5' cellspacing='0'>
				<tr><td valign='top'>
						input:<br/>
						<textarea cols='70' rows='20' name='string'><?php echo @$_GET['string'] ?></textarea><br/>
					<td valign='top'>
						ouput:<br/>
						<textarea cols='70' rows='20'><?php echo htmlentities($parsed, ENT_QUOTES) ?></textarea><br/>
				</tr>
				<tr><td valign='top' colspan='2' align='center'>
						<input type='submit' value='          parse          '><br/>
				</tr>
				<tr><td valign='top' colspan='2'>
						<?php echo $parsed ?>
				</tr>
				<tr>
					<td colspan='2'>
						possible codes:
						<pre>
[b]bold[/b]
[i]italic[/i]
[u]underline[/u]
[s]strike[/s]
[sub]subscript[/sub]
[sup]superscript[/sup]

[color=blue]blue text[/color]
[size=18]the size of this text is 18pt[/size]
[font=arial]different font type[/font]
[align=right]yes, you're right, this isn't on the left[/align]
he said: [quote=http://www.server.org/quote.html]i'm tony montana[/quote]
[code]x + y = 6;[/code]

http://www.server.org
[url]http://www.server.org[/url]
[url=http://www.server.org]server[/url]
[url=http://www.server.org t=new]server[/url]

guest@anonymous.org
[email]guest@anonymous.org[/email]
[email=guest@anonymous.org]mail me[/email]

[img]http://www.server.org/image.jpg[/img]
[img w=100 h=200]http://www.server.org/image.jpg[/img]

[ulist]
[*]unordered item 1
[*]unordered item 2
[/ulist]
[list]
[*]unordered item 1
[*]unordered item 2
[/list]

[list=1]
[*]ordered item 1
[*]ordered item 2
[/list]
[list=i]
[*]ordered item 1 type i
[li=4]ordered item 4 type i[/li]
[/list]
[list=I]
[*]ordered item 1 type I
[/list]
[list=a s=5]
[li]ordered item 5 type a[/li]
[*]ordered item 6 type a
[/list]
[list=A]
[li]ordered item 1 type A[/li]
[li=12]ordered item 12 type A[/li]
[/list]

[list=A s=3]
[li]ordered item 1, nested list:
    [list=I]
    [li]nested item 1[/li]
    [li]nested item 2[/li]
    [/list][/li]
[li]ordered item 2[/li]
[/list]
						</pre>
				</tr>
			</table>
		</form>
</html>
