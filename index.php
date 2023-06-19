<?php
// Create a new SimpleXMLElement object
$xml = new SimpleXMLElement('<root/>');

// Add a new child to the XML object
$title = $xml->addChild('title', 'This is the title');

// Save the XML content to a string
$xmlContent = $xml->asXML();

// Print the XML content
echo $xmlContent;
