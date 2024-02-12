<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2007-2024 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 */
namespace CeusMedia\Mail\Transport\SMTP;

use RangeException;

use function array_key_exists;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Mail_Transport_SMTP
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Mail
 *	@see			https://www.knownhost.com/wiki/email/troubleshooting/error-numbers
 *	@see			http://www.serversmtp.com/en/smtp-error
 */
class Code
{
	/**	@var	array<int,array<string,string>>		$codes */
	protected static array $codes	= [
		101		=> [
			'label'		=> 'Cannot open connection',
			'message'	=> 'The server is unable to connect.',
			'comment'	=> 'Try to change the server\'s name (maybe it was spelt incorrectly) or the connection port.',
		],
		111		=> [
			'label'		=> 'Connection refused or inability to open an SMTP stream',
			'message'	=> 'Connection refused or inability to open an SMTP stream.',
			'comment'	=> 'This error normally refers to a connection issue with the remote SMTP server, depending on firewalls or misspelled domains. Double-check all the configurations and in case ask your provider.',
		],
		211		=> [
			'label'		=> 'System status message or system help reply',
			'message'	=> 'System status message or help reply.',
			'comment'	=> 'It comes with more information about the server.',
		],
		214		=> [
			'label'		=> 'Help message',
			'message'	=> 'A response to the HELP command.',
			'comment'	=> 'It contains information about your particular server, normally pointing to a FAQ page.',
		],
		220		=> [
			'label'		=> '<domain> Service ready',
			'message'	=> 'The server is ready.',
			'comment'	=> 'It is just a welcome message. Just read it and be happy that everything is working (so far)!',
		],
		221		=> [
			'label'		=> '<domain> Service closing transmission channel',
			'message'	=> 'The server is closing its transmission channel. It can come with side messages like "Goodbye" or "Closing connection".',
			'comment'	=> 'The mailing session is going to end, which simply means that all messages have been processed.',
		],
		250		=> [
			'label'		=> 'Requested mail action okay, completed',
			'message'	=> 'Its typical side message is "Requested mail action okay completed": meaning that the server has transmitted a message.',
			'comment'	=> 'The opposite of an error: everything has worked and your email has been delivered.',
		],
		251		=> [
			'label'		=> 'User not local; will forward to <forward-path>',
			'message'	=> '"User not local will forward": the recipient\'s account is not on the present server, so it will be relayed to another.',
			'comment'	=> 'It is a normal transfer action. For other information check out our article on what is an SMTP server.',
		],
		252		=> [
			'label'		=> 'Can not verify user; accepting message for attempt',
			'message'	=> 'The server cannot verify the user, but it will try to deliver the message anyway.',
			'comment'	=> 'The recipient\'s email account is valid, but not verifiable. Normally the server relays the message to another one that will be able to check it.',
		],
		354		=> [
			'label'		=> 'Start mail input; end with <CRLF>.<CRLF>',
			'message'	=> 'The side message can be very cryptic ("Start mail input end <CRLF>.<CRLF>"). It is the typical response to the DATA command.',
			'comment'	=> 'The server has received the "From" and "To" details of the email, and is ready to get the body message.',
		],
		420		=> [
			'label'		=> '<domain> Timeout connection problem',
			'message'	=> '"Timeout connection problem": there have been issues during the message transfer.',
			'comment'	=> 'This error message is produced only by GroupWise servers. Either your email has been blocked by the recipient\'s firewall, or there is a hardware problem. Check with your provider.',
		],
		421		=> [
			'label'		=> '<domain> Service not available, closing transmission channel',
			'message'	=> 'The service is unavailable due to a connection problem: it may refer to an exceeded limit of simultaneous connections, or a more general temporary problem.',
			'comment'	=> 'The server (yours or the recipient\'s) is not available at the moment, so the dispatch will be tried again later.',
		],
		422		=> [
			'label'		=> 'Mailbox storage limit exceeded',
			'message'	=> 'The recipient\'s mailbox has exceeded its storage limit.',
			'comment'	=> 'Best is to contact the user via another channel to alert him and ask to create some free room in his mailbox.',
		],
		431		=> [
			'label'		=> 'Not enough storage or out of memory',
			'message'	=> 'Not enough space on the disk, or an "out of memory" condition due to a file overload.',
			'comment'	=> 'This error may depend on too many messages sent to a particular domain. You should try again sending smaller sets of emails instead of one big mail-out.',
		],
		432		=> [
			'label'		=> 'Recipients Exchange Server incoming mail queue stopped',
			'message'	=> 'Typical side-message: "The recipient\'s Exchange Server incoming mail queue has been stopped".',
			'comment'	=> 'It is a Microsoft Exchange Server\'s SMTP error code. You should contact it to get more information: generally it is due to a connection problem.',
		],
		441		=> [
			'label'		=> 'Recipients mail server is not responding',
			'message'	=> 'The recipient\'s server is not responding.',
			'comment'	=> 'There is an issue with the user\'s incoming server: yours will try again to contact it.',
		],
		442		=> [
			'label'		=> 'Connection dropped during transmission',
			'message'	=> 'The connection was dropped during the transmission.',
			'comment'	=> 'A typical network connection problem, probably due to your router: check it immediately.',
		],
		446		=> [
			'label'		=> 'The maximum hop count was exceeded for the message: an internal loop has occurred',
			'message'	=> 'The maximum hop count was exceeded for the message: an internal loop has occurred.',
			'comment'	=> 'Ask your SMTP provider to verify what has happened.',
		],
		447		=> [
			'label'		=> 'Your outgoing message timed out because of issues concerning the incoming server',
			'message'	=> 'Your outgoing message timed out because of issues concerning the incoming server.',
			'comment'	=> 'This happens generally when you exceeded your server\'s limit of number of recipients for a message. Try to send it again segmenting the list in different parts.',
		],
		449		=> [
			'label'		=> 'A routing error',
			'message'	=> 'A routing error.',
			'comment'	=> 'Like error 432, it is related only to Microsoft Exchange. Use WinRoute.',
		],
		450		=> [
			'label'		=> 'Mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons)',
			'message'	=> 'The mailbox has been corrupted or placed on an offline server, or your email has not been accepted for IP problems or blacklisting.',
			'comment'	=> 'The server will retry to mail the message again, after some time. Anyway, verify that is working on a reliable IP address.',
		],
		451		=> [
			'label'		=> 'Error in processing',
			'message'	=> 'Your ISP\'s server or the server that got a first relay from yours has encountered a connection problem.',
			'comment'	=> 'It is normally a transient error due to a message overload, but it can refer also to a rejection due to a remote antispam filter. If it keeps repeating, ask your SMTP provider to check the situation. (If you\'re sending a large bulk email with a free one that can be a common issue).',
		],
		452		=> [
			'label'		=> 'Insufficient system storage',
			'message'	=> 'Too many emails sent or too many recipients: more in general, a server storage limit exceeded.',
			'comment'	=> 'Again, the typical cause is a message overload. Usually the next try will succeed: in case of problems on your server it will come with a side-message like "Out of memory".',
		],
		471		=> [
			'label'		=> 'Message stopped within the SPAM detection chain',
			'message'	=> 'An error of your mail server, often due to an issue of the local anti-spam filter.',
			'comment'	=> 'Contact your SMTP service provider to fix the situation.',
		],
		500		=> [
			'label'		=> 'Syntax error, command unrecognized',
			'message'	=> 'A syntax error: the server could not recognize the command.',
			'comment'	=> 'It may be caused by a bad interaction of the server with your firewall or antivirus. Read carefully their instructions to solve it.',
		],
		501		=> [
			'label'		=> 'Syntax error in parameters or arguments',
			'message'	=> 'Another syntax error, not in the command but in its parameters or arguments.',
			'comment'	=> 'In the majority of the times it is due to an invalid email address, but it can also be associated with connection problems (and again, an issue concerning your antivirus settings).',
		],
		502		=> [
			'label'		=> 'Command not implemented',
			'message'	=> 'The command is not implemented.',
			'comment'	=> 'The command has not been activated yet on your own server. Contact your provider to know more about it.',
		],
		503		=> [
			'label'		=> 'Bad sequence of commands',
			'message'	=> 'The server has encountered a bad sequence of commands, or it requires an authentication.',
			'comment'	=> 'In case of "bad sequence", the server has pulled off its commands in a wrong order, usually because of a broken connection. If an authentication is needed, you should enter your username and password.',
		],
		504		=> [
			'label'		=> 'Command parameter not implemented',
			'message'	=> 'A command parameter is not implemented.',
			'comment'	=> 'Like error 501, is a syntax problem; you should ask your provider.',
		],
		510		=> [
			'label'		=> 'Invalid email address',
			'message'	=> 'Bad email address.',
			'comment'	=> 'One of the addresses in your TO, CC or BBC line does not exist. Check again your recipient\'s accounts and correct any possible misspelling.',
		],
		511		=> [
			'label'		=> 'Invalid email address',
			'message'	=> 'Bad email address.',
			'comment'	=> 'One of the addresses in your TO, CC or BBC line does not exist. Check again your recipient\'s accounts and correct any possible misspelling.',
		],
		512		=> [
			'label'		=> 'DNS error: the host server for the recipient\'s domain name cannot be found',
			'message'	=> 'NS error: the host server for the recipient\'s domain name cannot be found.',
			'comment'	=> 'Check again all your recipient\'s addresses: there will likely be an error in a domain name (like mail@domain.coom instead of mail@domain.com).',
		],
		513		=> [
			'label'		=> 'Address type is incorrect',
			'message'	=> 'Another problem concerning address misspelling. In few cases, however, it is related to an authentication issue.',
			'comment'	=> 'Double check your recipient\'s addresses and correct any mistake. If everything is ok and the error persists, then it is caused by a configuration issue (simply, the server needs an authentication).',
		],
		523		=> [
			'label'		=> 'The total size of your mailing exceeds the recipient server\'s limits',
			'message'	=> 'The total size of your mailing exceeds the recipient server\'s limits.',
			'comment'	=> 'Re-send your message splitting the list in smaller subsets.',
		],
		530		=> [
			'label'		=> 'Access denied',
			'message'	=> 'Normally, an authentication problem. But sometimes it is about the recipient\'s server blacklisting yours, or an invalid email address.',
			'comment'	=> 'Configure your settings providing a username+password authentication. If the error persists, check all your recipients\' addresses and if you have been blacklisted.',
		],
		541		=> [
			'label'		=> 'The recipient address rejected your message',
			'message'	=> 'Normally, it is an error caused by an anti-spam filter.',
			'comment'	=> 'Your message has been detected and labeled as spam. You must ask the recipient to whitelist you.',
		],
		550		=> [
			'label'		=> 'Mailbox unavailable (e.g., mailbox not found, no access, or command rejected for policy reasons)',
			'message'	=> 'It usually defines a non-existent email address on the remote side.',
			'comment'	=> 'Though it can be returned also by the recipient\'s firewall (or when the incoming server is down), the great majority of errors 550 simply tell that the recipient email address does not exist. You should contact the recipient otherwise and get the right address.',
		],
		551		=> [
			'label'		=> 'User not local; please try <forward-path>',
			'message'	=> '"User not local or invalid address – Relay denied". Meaning, if both your address and the recipient\'s are not locally hosted by the server, a relay can be interrupted.',
			'comment'	=> 'It is a (not very clever) strategy to prevent spamming. You should contact your ISP and ask them to allow you as a certified sender.',
		],
		552		=> [
			'label'		=> 'Exceeded storage allocation',
			'message'	=> 'Simply put, the recipient\'s mailbox has exceeded its limits.',
			'comment'	=> 'Try to send a lighter message: that usually happens when you dispatch emails with big attachments, so check them first.',
		],
		553		=> [
			'label'		=> 'Mailbox name not allowed (e.g., mailbox syntax incorrect)',
			'message'	=> 'That is, there is an incorrect email address into the recipients line.',
			'comment'	=> 'Check all the addresses in the TO, CC and BCC field. There should be an error or a misspelling somewhere.',
		],
		554		=> [
			'label'		=> 'Transaction failed',
			'comment'	=> 'The incoming server thinks that your email is spam, or your IP has been blacklisted. Check carefully if you ended up in some spam lists',
			'message'	=> 'This means that the transaction has failed. it is a permanent error and the server will not try to send the message again.',
		]
	];

	/**
	 *	...
	 *	@access		public
	 *	@static
	 *	@param		integer		$code		HTTP code
	 *	@return		object
	 */
	public static function explain( int $code ): object
	{
		$explained	= FALSE;
		$label		= 'Unknown SMTP status code: '.$code;
		$message	= 'Unknown SMTP status code: '.$code;
		$comment	= 'There is no explanation for this code since it is not supported by this library.';

		if( array_key_exists( $code, self::$codes ) ){
			$explained	= TRUE;
			$label		= self::$codes[$code]['label'];
			$message	= self::$codes[$code]['message'];
			$comment	= self::$codes[$code]['comment'];
		}
		return (object) [
			'explained'	=> $explained,
			'code'		=> $code,
			'label'		=> $label,
			'message'	=> $message,
			'comment'	=> $comment,
		];
	}

	/**
	 *	...
	 *	@access		public
	 *	@static
	 *	@param		integer		$code		HTTP code to get label for
	 *	@return		string
	 *	@throws		RangeException			if given code is unknown
	 */
	public static function getText( int $code ): string
	{
		if( !array_key_exists( $code, self::$codes ) )
			throw new RangeException( 'Unknown SMTP status code: '.$code );
		return self::$codes[$code]['label'];
	}
}
