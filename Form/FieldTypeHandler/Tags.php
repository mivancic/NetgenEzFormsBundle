<?php

namespace Netgen\Bundle\EzFormsBundle\Form\FieldTypeHandler;

use eZ\Publish\API\Repository\Repository;
use Netgen\TagsBundle\API\Repository\TagsService;
use Netgen\Bundle\EzFormsBundle\Form\FieldTypeHandler;
use eZ\Publish\SPI\FieldType\Value;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\API\Repository\Values\Content\Content;
use Netgen\TagsBundle\Core\FieldType\Tags\Value as TagsValue;
use Symfony\Component\Validator\Constraints as Assert;

class Tags extends FieldTypeHandler
{
    /**
     * @var TagsService
     */
    protected $tagsService;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Tags constructor.
     *
     * @param TagsService $tagsService
     * @param Repository $repository
     */
    public function __construct(TagsService $tagsService, Repository $repository)
    {
        $this->tagsService = $tagsService;
        $this->repository = $repository;
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

        $options['multiple'] = false;

        $subTreeLimit = $fieldDefinition->validatorConfiguration['TagsValueValidator']['subTreeLimit'];
        $parentTag = $subTreeLimit ? $this->tagsService->loadTag($subTreeLimit) : null;
        $childTags = $this->tagsService->loadTagChildren($parentTag);

        $tags = [];
        foreach($childTags as $tag) {

            $tags[(string)$tag->keyword] = $tag->id;

        }

        $maxTags = $fieldDefinition->validatorConfiguration['TagsValueValidator']['maxTags'];

        if ($maxTags === 0 || $maxTags > 1) {
            $options['multiple'] = true;
        }

        if ($maxTags > 1) {
            $options['constraints'][] = new Assert\LessThanOrEqual(["value" => $maxTags]);
        }

        $options['choice_list'] = new ArrayChoiceList(
            $tags,
            function ($choice) {
                return false === $choice || (is_array($choice) && empty($choice))? '0' : (string) $choice;
            }
        );

        $formBuilder->add($fieldDefinition->identifier, ChoiceType::class, $options);

    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function convertFieldValueToForm(Value $value, FieldDefinition $fieldDefinition = null)
    {
        $tags = [];

        if (!empty($value->tags)) {
            foreach($value->tags as $tag) {
                $tags[] = $tag->id;
            }
        }

        if (count($tags) === 1) {
            return array_pop($tags);
        }

        return $tags;
    }

    /**
     * {@inheritdoc}
     *
     * @return TagsValue
     */
    public function convertFieldValueFromForm($data)
    {
        $tags = [];

        if (empty($data)) {
            return new TagsValue([]);
        }

        foreach ((array)$data as $tagId) {
            $tags[] = $this->tagsService->loadTag($tagId);
        }

        return new TagsValue($tags);
    }
}