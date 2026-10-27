
## Changelog

- 2.7.2 (unreleased)
	* SMTP: Fix: Deliver to CC and BCC recipients as well, not only to TO recipients; messages without TO recipient (e.g. BCC only) are sent.
	* SMTP: Fix: Double leading dots of content lines (RFC 5321, 4.5.2), a single dot line ended the message early.
	* SMTP: Fix: A rejected recipient does not stop sending to the others: result per recipient, 251 (will forward) accepted, no DATA if none is accepted.
	* SMTP: Fix: Send QUIT with line ending (CRLF), without warning if server closed connection already.
	* SMTP: Detect EHLO host name (fully qualified host name or address literal), new method setHeloHost.
	* SMTP: Send EHLO again after STARTTLS (RFC 3207).
	* SMTP: Support implicit TLS (SMTPS, port 465), new method setImplicitTls.
	* SMTP: Hide credentials in verbose output.
	* SMTP: New option to switch off certificate validation: setSecure( $secure, $validateCertificate ).
	* SMTP: New method setSslOptions to set SSL context options, e.g. a CA file; options of a socket set by setSocket are kept.
	* SMTP: New method setReadTimeout, socket waits 30 seconds for a response by default.
	* SMTP socket: Connect with stream context, used on implicit TLS and STARTTLS.
	* SMTP socket: Report timeout (new error code Response::ERROR_TIMEOUT), closed connection, missing greeting (with hint on implicit TLS port) and reason of failed TLS handshake, instead of PHP warnings.
	* Local transport: Fix: Send body without headers, it was unreadable.
	* Local transport: Fix: Deliver to BCC recipients by Bcc header for sendmail -t, replacing Bcc header of message.
	* Local transport: Send messages without TO recipient (e.g. BCC only), To set to empty group undisclosed-recipients:;.
	* Local transport: Send one message for all recipients, avoid duplicate headers.
	* Local transport: Join additional mail parameters by space instead of line break.
	* Local transport: Remove check for line breaks in subject and names, rendering prevents header injection; line breaks in subject become a space, like for SMTP.
	* Message: Fix: Encode names in From and Disposition-Notification-To headers (RFC 2047), like in To, Cc and Reply-To.
	* Message: To, Cc and Reply-To as one header each, listing all addresses (RFC 5322), folded.
	* Message: Add same recipient or reply-to address only once; a name given again is taken, if missing before.
	* Message: getReplyTo reads address lists and decodes names; unreadable values are skipped, and replaced by addReplyTo.
	* Message: setReadNotificationRecipient replaces recipient set before.
	* Message: New method hasRecipients, for recipients of any type.
	* Message parser: Keep ASCII To/Cc headers as they are (e.g. groups), add recipients to list directly.
	* Message renderer: Fix: Never render Bcc header, e.g. of parsed messages, blind copy recipients were visible to others.
	* Message renderer: Fix: Replace line breaks in subject by a space (header injection).
	* Header renderer: Fix: Prevent header injection, reject line breaks in header values (except folding) and invalid header names.
	* Header renderer: Fix: Encode non-ASCII parameter values (RFC 2231) instead of raw 8bit; fold before parameter not fitting in line.
	* Header renderer: Remove unused encoding options; method setEncodeOption deprecated, without effect.
	* Header encoding: Fix: encodeIfNeeded returned values with line breaks unencoded.
	* Header encoding: Fix: Q encoding by RFC 2047 instead of body quoted-printable (underscore, quotes, comma), valid in names.
	* Header encoding: Fix: Fold base64 encoded values.
	* Header encoding: Encode by own RFC 2047 encoder only, independent of PHP version, new method encodeWords: whole characters per encoded word, words up to 75 and lines up to 76 characters.
	* Header encoding: encodeIfNeeded keeps printable ASCII plain, folded at spaces; new parameter offset for header name length, applied for subject.
	* Header encoding: Decode by own decoder only: adjacent encoded words joined by octets (also characters split across words), unknown charsets and malformed words kept, independent of PHP version and iconv.
	* Header encoding: Fix: Decoded line breaks become a space, they broke the header (injection on rendering again).
	* Header encoding, parser: Possessive patterns for quoted strings, phrases and comments, long values (from about 10 KB) exhausted PCRE stack and lost parameters.
	* Header encoding: Remove encode strategies (own, mb_encode_mimeheader) with methods setEncodeStrategy and setEncodeStrategyFallback.
	* Header encoding: Remove decode strategies (iconv, iconv strict, iconv tolerant, own fallback) with methods setDecodeStrategy, setDecodeStrategyFallback and decodeByOwnStrategy.
	* Header encoding: New static method isPrintableAscii, used instead of copied checks.
	* Header encoding: New method decodeAddressListIfNeeded, decodes names (and comments) one by one and quotes them if needed.
	* Header encoding: New method convertToCharset, unknown charset: valid UTF-8 kept, else read as Latin-1.
	* Header encoding: New static methods quote, unquote, unfold, getLastLineLength and renderParameter (RFC 2231); new constants LINE_LENGTH, WORD_LENGTH and PATTERN_*.
	* Header parser: Fix: Decode address lists (From, To, Cc, Reply-To, ...) name by name, an encoded name with comma was split into two addresses.
	* Header parser: Fix: Split only Content-Type and Content-Disposition into value and parameters, subjects with semicolon stay complete; semicolon in quoted parameter value does not split.
	* Header parser: Fix: Read RFC 2231 parameters (filename*=, continuations, charsets), plus sign kept, extended value wins; parameter names case-insensitive.
	* Header field: getAttribute finds attributes case-insensitive.
	* Header parser: Fix: Folded lines joined by one space, deeper indented lines glued words together.
	* Header parser: Decode encoded words in parameter values after splitting.
	* Header parser: Fix: Bare CR (not before LF) becomes a space, parsed message could not be rendered again.
	* Header parser: Skip fields with invalid names (not printable ASCII or containing colon), they could not be rendered again.
	* Header parser: Remove strategies (STRATEGY_*) with methods setStrategy, parseByOwnStrategy and parseByIconvStrategy.
	* Address renderer: Fix: Escape quotes and backslashes in names.
	* Address renderer: New methods setEncodeName and setLineLength; encoded name rendered for position in line (new parameter offset), folded before address if needed.
	* Address collection renderer: New methods setEncodeNames, setLineLength and setOffset, for use in headers; addresses are not split if they fit on next line.
	* Address collection renderer: Encode non-ASCII names as a whole, special characters like comma stay inside encoded words.
	* Address collection parser: Fix: Read quoted pairs in quoted names (own method), e.g. "Max \\"Mo\\" Muster".
	* Address collection parser: IMAP method skips groups (e.g. "undisclosed-recipients:;") and syntax errors.
	* Attachment, inline image: Fix: Encode file names (RFC 2231), quote and escape plain ones, split long ones into continuations; encode Content-Description.
	* Attachment: Add file name to Content-Type (name), for clients reading only this one.
	* Part: Fix: Keep 7bit and 8bit content unchanged, no double UTF-8 encoding, no line breaks within words.
	* Part: Break 7bit and 8bit lines only if longer than 998 octets, between UTF-8 characters if possible.
	* Part: Fix: Send 7bit content containing non-ASCII as quoted-printable, it was 8bit data labelled 7bit.
	* Part: New method applyHeaders, sets content headers without rendering.
	* Util: New string helper Str replaces trait Conduct\RegularStringHandling: hasContent (also for NULL), regMatch (matches as third argument), regMatchAll, regReplace and regSplit; exceptions name PCRE error (e.g. stack limit).
	* Use Str::hasContent instead of strlen/trim checks; header parser and SMTP socket use Str::regMatch, a PCRE failure is reported instead of handled like no match.
	* Tests: Add integration tests against local Mailpit instances (util/mailpit), composer scripts dev:mailpit-up, dev:mailpit-down and dev:phpunit-mailpit.
	* Tests: Add tests for local transport using a fake sendmail.
	* Doc: Readme: SMTP options, local transport (needs sendmail option -t), header encoding, Mailpit tests.
	* Doc: Add concept for DKIM, PGP and S/MIME, and open todos (doc/todo-signing-encryption.md).
- 2.7.1
	* Support upload of raw mail message to mailbox folder.
	* Add Message::removePart.
	* Improve address collection filter.
	* Add unit tests for message parser and improve parser.
	* Secure HTTP links.
	* Fall back to PHPStan level 7 after update to PHPStan 2.
	* Update copyright year and packages.
- 2.7.0
	* New entity for transport results: SMTP and Local transport return list of result entities on send.
	* Message holds library version.
	* Mailbox::getMail* methods and Mailbox\Search::getAll return mailbox mail entities.
	* Mailbox mail entity and mailbox search receive mailbox connection instead of IMAP\Connection.
	* Fix bug in Deprecation.
	* Drop support for Common 0.9.x, DocCreator 0.9.x and Cache 0.5.x.
	* Update tests, demos and config files.
- 2.6.2
	* Support mailbox mail message upload (imap_append).
	* Make charset in message part optional.
	* CLI demo client uses CeusMedia\Common\CLI\Password.
	* Keep PHPStan level max, update tests and demos.
- 2.6.1
	* Reach PHPStan level max + strict again after update.
	* Update copyright and badges.
- 2.6.0
	* Switch to PHP 8.1 and PHPUnit 10.1.
	* Apply CeusMedia/Common namespaces, strictly typed properties and method arguments.
	* Improve code following PHPStan and Rector.
	* Update exception handling and documentation.
	* Update composer: packages, scripts and tool config files.
	* Update unit tests and test integration.
- 2.5.3
	* Support Common 0.9, introducing namespaces.
	* Last release before big changes.
- 2.5.2
	* Add parser for Received headers.
	* Add address collection filter.
	* Extend encoding by fallback strategy.
	* Extract user agent detection to new library class.
	* Extract preg_* handling to own trait.
	* Use deprecation from CeusMedia/Common v0.8.6, improve extensibility of deprecation class.
	* Simplify response handling in availability check.
	* Support CeusMedia/Cache 0.3 with PSR base.
	* Support PHP 8.
	* Reach PHPStan max level.
	* Increase test coverage, improve code coverage report.
	* Fix bugs.
- 2.5.1
	* Be less tolerant on parsing addresses.
	* Merge attributed header field into header field.
	* Remove old parser strategies.
	* Add mail viewer demo, update demos and demo mails.
- 2.5
	* Add Message::getReplyTo.
	* Add SMTP exception and implement SMTP response.
	* Extract rendering of header sections and fields to header renderer.
	* Let header section implement countable interface.
	* Support encoding strategies.
	* Support quotes in header field attributes.
	* Turn address name into data object.
	* Extract connection from mailbox.
	* Improve parsing and rendering of message parts and header.
	* Reach PHPStan level 9 (8 + extra strict rules).
- 2.4.2
	* Support VRFY on address check.
	* Support mailbox folder encoding (UTF7-IMAP).
	* Improve connection error handling.
	* Relax message parser on attachment headers.
	* Update cache integration and default user agent version.
	* Apply Rector and add deprecations.
	* Split tests into unit and integration tests.
	* Improve code quality, fix bugs.
- 2.4.1
	* Add encoding strategies: own strategy and 3 iconv strategies.
	* Add attributed header field, move attributed header field parser to header parser.
	* Hide warning on failed connection.
	* Refactor web parse demo to demo app.
- 2.4
	* Support PHPUnit 9, refactor unit tests to use namespaces.
	* Improve code style to complete PHPStan level 6.
	* Update copyright year and demos.
- 2.3.4
	* Add PHPStan, improve code style to reach PHPStan level 3.
- 2.3.3
	* Support PHP 8.
	* Improve message header parser.
	* Add badges to readme, add demo mail with complex headers.
- 2.3.2
	* Provide character set for content decoding.
	* Add HTML purifier in composer file.
	* Finish CLI demo client, update web mail demo.
	* Fix bug.
- 2.3.1
	* Add crypto mode in SMTP transport, throw exception if crypto negotiation fails.
	* Allow returning NULL on getters of general part class.
	* Fix bug in message parser for parts with Content-Disposition of INLINE.
	* Fix bugs on message part type detection.
	* Add CLI demo client and demo to show crypto constants, extend web demos.
- 2.3
	* Move arguments of Message::getAttachments to getParts.
	* Improve inline image part and mail part type handling.
	* Add prototype of mail client and of structure for mailbox searches.
	* Improve code quality, tests and demos, fix bugs.
- 2.2
	* Replace static construction (create) by getInstance.
	* Improve user agent version handling.
	* Improve attachment rendering.
	* Change needed PHP version to 7.1.
	* Add PhanViewer for dev mode, fix possible bugs found by Phan.
	* Improve code doc and type hints, add and improve CLI demos.
- 2.1
	* Migrate code to support PHP 7.1, type hinting and coding style.
	* Add static constructor.
	* Improve message header encoding.
	* Cleanup address check and use MX class.
	* Improve code doc, test coverage and CLI parse demo, fix bugs.
- 2.0
	* Add basic mailbox class to connect with IMAP server, store last error in mailbox.
	* Add resolver for DNS MX records.
	* Add DMARC model, parser and renderer.
	* Add address collection, SMTP socket and SMTP test (with mock socket).
	* Add collection parser using IMAP function, detect installed IMAP extension.
	* Let parsers return address objects instead of data objects, make address stringable.
	* Allow address to be given as address object, allow third syntax pattern in address parser.
	* Rewrite parser to support not multipart messages and header field folding.
	* Automatically decode header field values and content of attachment parts on parsing.
	* Support forwarded mails as attachment part.
	* Support multiple addresses in BCC header field.
	* Spread recipients list in message by participation types.
	* Render message with only one part not as multipart, let all parts render into given headers.
	* Improve multipart nesting and local transport.
	* Set quoted-printable as default encoding for subject and text parts.
	* Add method to get message parts, with or without inline images.
	* Support chainability in address and message classes.
	* Remove static mode of parsers and renderers.
	* Move secure detection to port management, improve closing of SMTP socket connection.
	* Show last SMTP response line on error, add method to return socket error map.
	* Remove MX record detection on mailbox connect, add host and timeout to configure.
	* Use Phan for static code analysis, fix problems found.
	* Remove Mail.ini in favour of Mail.ini.dist.
	* Refactor test environment, add test with real mail sending and receiving.
	* Reorganize and update demos, add CLI parser, renderer and mailbox demos.
- 1.3.1
	* Fix bug.
- 1.3.0
	* Stability improvements.
- 1.2.9
	* Last update for version 1.x.
	* Update tests.
- 1.2.8
	* Support custom attachment file names.
- 1.2.7
	* Support Reply-To in message.
	* Migrate local transport due to inner changes.
- 1.2.6
	* Fix bugs.
- 1.2.5
	* Add method to remove message part.
	* Update composer file.
- 1.2.4
	* Fix bug, update tests.
- 1.2.3
	* Avoid having many From headers.
	* Fix PHPUnit file.
- 1.2.2
	* Support encoded names in address list parser, decode names to UTF-8.
- 1.2.1
	* Add address list parser.
- 1.2
	* Use base64 as default encoding.
- 1.1.4
	* Fix bug in recipient checker.
- 1.1.3
	* Use extended SMTP on recipient check.
- 1.1.2
	* Add SMTP code resolution.
- 1.1.1
	* Add PHPUnit in composer file.
	* Add PHPUnit configuration file.
	* Move changelog.md to doc folder.
	* Move doc/doc.xml to root folder.
	* Generalized make file.
- 1.1.0
	* New SMTP transport method setAuth.
	* Improve chainability of Message.
	* New Message methods: addFile, addHtml, addText, embedImage.
	* Deprecated Message methods: addAttachment, attachFile, embedImage.
	* New Message method getUserAgent replaces getAgent.
	* New Message method setUserAgent replaces setAgent.
	* Deprecated Message methods: getAgent, setAgent.
	* Improve code documentation.
	* Let Renderer use set user agent of Message.
	* Deprecated Renderer methods: getAgent, setAgent.
	* New Image method setId.
	* Move Image method setFile into constructor.
	* Move Image method getMimeTypeFromFile to abstract part.
	* Apply MIME detection in attachment part.
	* Migrate and extend test classes.
	* Add deprecation notice trigger for all deprecated methods.
	* Update demos.
	* Update make file to use colors on unit test.
	* Update make file by syntax test.
- 1.0.1 Cleanup.
- 1.0.0 Release.
- 0.9.6 Update readme.
- 0.9.5 Allow participient object or address string as sender and receivers.
- 0.9.4 Fix code doc.
- 0.9.3 Allow participiant or address string as receiver argument.
- 0.9.2 Detect TLS/SSL secured connection by port.
- 0.9.1 Add full console demo.
- 0.9.0 Replace generation algorithm for inner boundary.
- 0.8.9 Require CeusMedia/DocCreator only in dev mode.
- 0.8.8 Require CeusMedia/Common version 0.8.2 in composer file.
- 0.8.7 Add support for inline images.
- 0.8.6 Use participant for sender and recipients.
- 0.8.5 Add make target to generate documentation.
- 0.8.4 Prepare for automated documentation generation using DocCreator.
- 0.8.3 Set file permissions.
- 0.8.2 Add test script in demo and append doc folder to ignore list.
- 0.8.1 Update make file to save unit test coverage report in doc folder.
- 0.8.0 Add more unit test classes.
- 0.7.9 Define maximum line length statically.
- 0.7.8 Improve code doc and add strict mode switches for testing.
- 0.7.7 Use content encoding of abstract part class and get file stats on setFile.
- 0.7.6 Support charset and encoding on construction.
- 0.7.5 Extract content encoding to abstract part class.
- 0.7.4 Add setFields and removeFieldByName.
- 0.7.3 Improve header field key rendering.
- 0.7.2 Handle exceptions.
- 0.7.1 Set mail sender and receivers in brackets.
- 0.7.0 Fix syntax error.
- 0.6.9 Rename default transport to local transport.
- 0.6.8 Add CeusMedia/Cache as requirement.
- 0.6.7 Reactivate unit test classes after migration.
- 0.6.6 Improve code syntax.
- 0.6.5 Extended attachment part class for reading mails with attached files.
- 0.6.4 Fixed parsing of multipart mails.
- 0.6.3 Add CLI demo script.
- 0.6.2 Remove obsolete encoding check.
- 0.6.1 Update readme.md
- 0.6.0 Extend demo by chained example code.
- 0.5.9 Improve handling of SMTP responses and named participants.
- 0.5.8 Improve encoding of header value of mail subject.
- 0.5.7 Improve encoding of mail header values, like subject or sender name.
- 0.5.6 Improved content encoding.
- 0.5.5 Cleanup SMTP transport.
- 0.5.4 Add demo.
- 0.5.3 Improve SMTP communication and exception handling.
- 0.5.2 Fix @package in code doc blocks.
- 0.5.1 Migrate classes to support namespaces.
- 0.5.0 Move all class files in src and test folders.
- 0.4.9 Updated recipient checker.
- 0.4.8 Updated make file and test bootstrap.
- 0.4.7 Ordered methods in Mail_Check_Recipient.
- 0.4.6 Added prototype of mail parser.
- 0.4.5 Added address check and test classes.
- 0.4.4 Added participant test and fixed bug in participant.
- 0.4.3 Added make file.
- 0.4.2 Shorter base64 chunk length.
- 0.4.1 Subjects will be base64 encoded.
- 0.4.0 Mail: Added participant and corrected code doc.
- 0.3.1 Mail: Updated attachment.
- 0.3.0 Mail: Updated code doc and doc config. Added readme file.
- 0.2.0 Mail: Added doc files.
- 0.1.1 Mail: Cleanup.
- 0.1.0 Added new module Mail.
