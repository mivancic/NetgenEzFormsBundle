<?php

namespace Netgen\Bundle\EzFormsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class XmlTextType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'ezforms_xml';
    }


    public function getParent()
    {
        return TextareaType::class;
    }
}