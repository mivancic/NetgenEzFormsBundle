<?php
namespace Netgen\Bundle\EzFormsBundle\Form\FieldTypeHandler;

use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\SPI\FieldType\Value;
use Netgen\Bundle\EzFormsBundle\Form\FieldTypeHandler;
use Netgen\Bundle\MoreBundle\Helper\SortClauseHelper;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\API\Repository\Values\Content\Content;
use Netgen\Bundle\MoreBundle\Core\FieldType\RelationList\Value as RelationListValue;

use eZ\Publish\API\Repository\Repository;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;

class RelationList extends FieldTypeHandler
{
    /** @var Repository */
    private $repository;

    /** @var SortClauseHelper */
    private $sortClauseHelper;

    /**
     * @param Repository $repository
     */
    public function __construct(
        Repository $repository,
        SortClauseHelper $sortClauseHelper
    )
    {
        $this->repository = $repository;
        $this->sortClauseHelper = $sortClauseHelper;
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

        $fieldSettings = $fieldDefinition->getFieldSettings();
        $selectionMethod = $fieldSettings['selectionMethod'];
        $defaultLocation = $fieldSettings['selectionDefaultLocation'];
        $contentTypes = $fieldSettings['selectionContentTypes'];

        $choiceList = $this->getChoiceListOptions( $defaultLocation ? $defaultLocation : 2, $contentTypes );

        $options['choice_list'] = new ChoiceList( array_keys( $choiceList ), array_values( $choiceList ) );
        $options['choices_as_values'] = true;

        // display checkbox, radiobutton or selectbox
        $mappedOptions = $this->displayOptionMap($selectionMethod);
        $options['expanded'] = $mappedOptions['expanded'];
        $options['multiple'] = $mappedOptions['multiple'];

        //disable empty values
        $options['empty_value'] = "Please select one";

        $formBuilder->add($fieldDefinition->identifier, 'choice', $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return RelationListValue
     */
    public function convertFieldValueFromForm($value)
    {
        return new RelationListValue((array) $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param RelationListValue $value
     *
     * @return array
     */
    public function convertFieldValueToForm(Value $value, FieldDefinition $fieldDefinition = null)
    {
        return $value->destinationContentIds;
    }

    protected function getChoiceListOptions( $selectionDefaultLocationId, $contentTypes = array() )
    {
        $contentService = $this->repository->getContentService();
        $locationService = $this->repository->getLocationService();

        $selectionDefaultLocation = $locationService->loadLocation($selectionDefaultLocationId);

        $query = new LocationQuery();
        $criteria = array(
            new Criterion\ParentLocationId( $selectionDefaultLocationId ),
        );

        if (!empty($contentTypes))
        {
            array_push($criteria, new Criterion\ContentTypeIdentifier( $contentTypes ));
        }

        $query->query = new Criterion\LogicalAnd( $criteria );

        $query->sortClauses = array(
            $this->sortClauseHelper->getSortClauseBySortField(
                Location::SORT_FIELD_NAME,
                Location::SORT_ORDER_ASC
            )        );
        // Disable permission checking
        $searchResult = $this->repository->getSearchService()->findLocations($query);

        if ( $searchResult->totalCount > 0 )
        {
            $items = array();
            foreach ( $searchResult->searchHits as $searchHit )
            {
                $items[$searchHit->valueObject->contentInfo->id] = $searchHit->valueObject->contentInfo->name;
            }
            return $items;
        }

        return array();
    }

    protected function displayOptionMap($selectionMethod)
    {
        switch ($selectionMethod){
            // 8 => Autocomplete single
            case 8:
                $expanded = false;
                $multiple = false;
                break;
            // 4 => Multiple selection
            case 4:
                $expanded = false;
                $multiple = true;
                break;
            // 3 => CheckBoxes
            case 3:
                $expanded = true;
                $multiple = true;
                break;
            // 2 => Radio buttons
            case 2:
                $expanded = true;
                $multiple = false;
                break;
            // 1 => DropDown
            case 1:
            default:
                $expanded = false;
                $multiple = false;
                break;
        }

        return array(
            'expanded' => $expanded,
            'multiple' => $multiple
        );
    }
}