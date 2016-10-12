
## Changelog

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
- 0.7.7 Use content encoding of abstract part class and get file stats on set…  …
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
