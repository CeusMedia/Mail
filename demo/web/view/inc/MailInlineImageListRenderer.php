<?php
use CeusMedia\Bootstrap\Icon;
use CeusMedia\Mail\Message;
use CeusMedia\Common\Alg\UnitFormater as UnitFormater;
use CeusMedia\Common\UI\HTML\Elements as HtmlElements;
use CeusMedia\Common\UI\HTML\Tag as Tag;

class MailInlineImageListRenderer
{
	protected string $file;
	protected Message $message;

	public function __construct( string $file, Message $message )
	{
		$this->file		= $file;
		$this->message	= $message;
	}

	public function render(): string
	{
		$iconDownload		= new Icon( 'download' );
		$iconView			= new Icon( 'eye' );
		$iconFile			= new Icon( 'file' );

		if( !$this->message->hasInlineImages() )
			return '';
		$list = [];
		foreach( $this->message->getInlineImages() as $nr => $image ){
			$buttonDownload	= Tag::create( 'a', $iconDownload.' speichern', [
				'href'	=> './?file='.urlencode( $this->file ).'&action=download&type=image&part='.$nr.'&id='.urlencode( $image->getId() ),
				'class'	=> 'btn btn-small',
			] );
			$buttonView		= Tag::create( 'a', $iconView.' öffnen', [
				'href'	=> './?file='.urlencode( $this->file ).'&action=view&type=image&part='.$nr.'&id='.urlencode( $image->getId() ),
				'class'	=> 'btn btn-small',
			] );
			$buttons	= Tag::create( 'div', [ $buttonView, $buttonDownload ], [
				'class'	=> 'btn-group',
			] );
			$date		= '';
			if( $image->getFileMTime() ){
				$date	= date( 'Y-m-d H:i:s', $image->getFileMTime() );
			}
			$link		= Tag::create( 'a', $iconFile.' '.$image->getFileName(), [
				'href'	=> './?file='.urlencode( $this->file ).'&action=view&type=image&part='.$nr.'&id='.urlencode( $image->getId() ),
			] );
			$list[]	= Tag::create( 'tr', [
				Tag::create( 'td', $link ),
				Tag::create( 'td', $image->getMimeType() ),
				Tag::create( 'td', UnitFormater::formatBytes( $image->getFileSize() ) ),
				Tag::create( 'td', $date ),
				Tag::create( 'td', $buttons, [ 'style' => 'text-align: right' ] ),
			] );
		}
		$heads	= Tag::create( 'tr', [
			Tag::create( 'th', 'Dateiname' ),
			Tag::create( 'th', 'MIME-Type' ),
			Tag::create( 'th', 'Dateigröße' ),
			Tag::create( 'th', 'letzte Änderung' ),
			Tag::create( 'th', '' ),
		], [ 'style' => 'background-color: rgba(255, 255, 255, 0.75);' ] );
		$colgroup	= HtmlElements::ColumnGroup( '', '15%', '10%', '20%', '15%' );
		$thead		= Tag::create( 'thead', $heads );
		$tbody		= Tag::create( 'tbody', $list );
		return Tag::create( 'table', [ $colgroup, $thead, $tbody ], [ 'class' => 'table not-table-condensed table-striped' ] );
	}
}
