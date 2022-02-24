<?php

use CeusMedia\Bootstrap\Icon;
use CeusMedia\Bootstrap\Nav\Tabs;
use CeusMedia\Mail\Message;
use CeusMedia\Mail\Message\Part\HTML as HtmlPart;
use CeusMedia\Mail\Message\Part\Text as TextPart;
use CeusMedia\Mail\Message\Part\Attachment as AttachmentPart;
use CeusMedia\Mail\Message\Part\InlineImage as InlineImagePart;
use CeusMedia\Mail\Message\Part\Mail as MailPart;
use UI_HTML_Tag as Tag;

Icon::$defaultSet = 'fontawesome';

$tabs	= new Tabs( 'mail-contents' );

//  FACTS PANEL
$content	= (new MailFactsRenderer( $file, $message ))->render();
$tabs->add( 'mail-content-info', '#', 'Facts', $content );


//  PART: HTML
$content	= '';
if( $message->hasHTML() ){
	$content	= Tag::create( 'iframe', '', array(
		'src'			=> './?file='.urlencode( $file ).'&action=view&type=html',
		'frameborder'	=> '0',
		'style'			=> "width: 98%; height: 600px; border: 1px solid gray; border-radius: 2px;",
	) );
}
$tabs->add( 'mail-content-html', '#', 'HTML', $content, '' === $content );


//  PART: PLAIN TEXT
$content	= '';
if( $message->hasText() ){
	$content	= Tag::create( 'pre', $message->getText()->getContent(), array(
		'style' => "width: 98%; height: 600px; border: 1px solid gray; overflow: auto; border-radius: 2px;",
	) );
}
$tabs->add( 'mail-content-plain', '#', 'Plain Text', $content, '' === $content );


//  PART: ATTACHMENTS
$content	= (new MailAttachmentListRenderer( $file, $message ))->render();
$tabs->add( 'mail-content-attachment', '#', 'Attachments', $content, '' === $content );


//  PART: INLINE IMAGES
$content	= (new MailInlineImageListRenderer( $file, $message ))->render();
$tabs->add( 'mail-content-image', '#', 'Inline Images', $content, '' === $content );


//  PART: SOURCE
$helperSource	= new MailSourceRenderer( $message );
$valueSource	= htmlentities( $helperSource->render(), ENT_QUOTES, 'UTF-8' );
$content		= Tag::create( 'pre', $valueSource, array(
	'style'	=> "max-height: 600px; scroll-y: auto; overflow: auto",
) );
$tabs->add( 'mail-source', '#', 'Source Code', $content, '' === $content );


$optFile	= UI_HTML_Elements::Options( array_combine( array_values( $files ), array_values( $files ) ), $file );

return '
<form action="./" method="GET">
	<select name="file" onchange="this.form.submit()">
		'.$optFile.'
	</select>
</form>
<div class="content-panel">
	<div class="content-panel-inner">
		'.$tabs->render().'
	</div>
</div>
<style>
li > span.list-item-key {
	border: 1px solid #AAA;
	background-color: #EEE;
	border-radius: 3px;
	padding: 0px 4px;
	display: inline-block;
}

</style>';
