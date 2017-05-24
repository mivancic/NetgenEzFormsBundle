<?php

namespace Netgen\Bundle\EzFormsBundle\Form\FieldTypeHandler;

use Netgen\Bundle\EzFormsBundle\Form\FieldTypeHandler;
use eZ\Publish\SPI\FieldType\Value;
use Netgen\Bundle\EzFormsBundle\Form\Type\XmlTextType;
use Symfony\Component\Form\FormBuilderInterface;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\Core\FieldType\XmlText\Value as XmlTextValue;
use eZ\Publish\Core\FieldType\XmlText\Type;

class XmlText extends FieldTypeHandler
{
    /**
     * @var Type
     */
    protected $type;

    /**
     * XmlText constructor.
     *
     * @param Type $type
     */
    public function __construct(Type $type)
    {
        $this->type = $type;
    }
    /**
     * {@inheritdoc}
     */
    protected function buildFieldForm(
        FormBuilderInterface $formBuilder,
        FieldDefinition $fieldDefinition,
        $languageCode,
        Content $content = null
    ) {
        $options = $this->getDefaultFieldOptions($fieldDefinition, $languageCode, $content);

        $options['block_name'] = 'ezforms_xml';

        $formBuilder->add($fieldDefinition->identifier, XmlTextType::class, $options);
    }

    /**
     *
     *
     * @param Value $value
     * @param FieldDefinition $fieldDefinition
     *
     * @return array
     */
    public function convertFieldValueToForm(Value $value, FieldDefinition $fieldDefinition = null)
    {
        if (!$value instanceof XmlTextValue) {
            return;
        }

        if ($this->type->isEmptyValue($value)) {
            return '';
        }

        $xml = '';
        foreach ($value->xml->childNodes as $node) {
            $xml = $xml . $value->xml->saveXML($node);
        }

        return $xml;
    }

    /**
     * {@inheritdoc}
     *
     * @return XmlTextValue
     */
    public function convertFieldValueFromForm($data)
    {
        // $contentID is used as protection for the self embedding object.
        // This is simple implementation of XMLText so it can be hardcoded to 1
        $contentID = 1;
        $parser = new \eZSimplifiedXMLInputParser($contentID);
        $parser->setParseLineBreaks(true);

        $processedData = $parser->process(trim($data));
        $xml = new XmlTextValue($processedData);

        return $xml;
    }
}