<?php
require_once LIB_DIR . '/globals.php';

/* The namespace/name pairs for DC terms metadata */
global $AUTHOR_ELEMENT, $TITLE_ELEMENT, $DATE_ELEMENT, $PUBLISHER_ELEMENT, $SOURCE_ELEMENT;
$AUTHOR_ELEMENT = array('Dublin Core', 'Creator');
$TITLE_ELEMENT = array('Dublin Core', 'Title');
$DATE_ELEMENT = array('Dublin Core', 'Date');
$PUBLISHER_ELEMENT = array('Dublin Core', 'Publisher');
$SOURCE_ELEMENT = array('Dublin Core', 'Source');
global $EDITOR_ELEMENT, $JOURNAL_ELEMENT, $BOOK_ELEMENT, $INSTITUTION_ELEMENT, $VOLUME_ELEMENT;
global $NUMBER_ELEMENT, $PAGES_ELEMENT, $DOI_ELEMENT, $ISBN_ELEMENT, $URL_ELEMENT, $LOCAL_URL_ELEMENT;
global $DIRECTOR_ELEMENT, $PRODUCER_ELEMENT;
$EDITOR_ELEMENT = array('Item Type Metadata', 'Editor');
$JOURNAL_ELEMENT = array('Item Type Metadata', 'Journal');
$BOOK_ELEMENT = array('Item Type Metadata', 'Book');
$INSTITUTION_ELEMENT = array('Item Type Metadata', 'Institution');
$VOLUME_ELEMENT = array('Item Type Metadata', 'Volume');
$NUMBER_ELEMENT = array('Item Type Metadata', 'Number');
$PAGES_ELEMENT = array('Item Type Metadata', 'Pages');
$DOI_ELEMENT = array('Item Type Metadata', 'DOI');
$ISBN_ELEMENT = array('Item Type Metadata', 'ISBN');
$URL_ELEMENT = array('Item Type Metadata', 'URL');
$LOCAL_URL_ELEMENT = array('Item Type Metadata', 'Local URL');
$DIRECTOR_ELEMENT = array('Item Type Metadata', 'Director');
$PRODUCER_ELEMENT = array('Item Type Metadata', 'Producer');

/** Wrap the sortable shortcodes */
add_shortcode('items', 'bigstuff_sortable_items_shortcode');
add_shortcode('collections', 'bigstuff_sortable_collections_shortcode');

/**
 * Get a theme option wuith a default value
 *
 * @param string $option The theme option name
 * @param $def The default value if the option is not set
 * @param string|null $theme The theme name (defaults to the current theme)
 * @return string
 */
function get_theme_option_with_default($option, $def, $theme = null) {
    $value = get_theme_option($option, $theme);
    if ($value == null || strlen($value) == 0)
        $value = $def;
    return $value;
}

/**
 * Try and extract a year from a date.
 * <p>
 * This uses the PHP parse_date function.
 * Finding a year is conditional on the date being in a parsable for, otherwise the entire date is returned.
 *
 * @param string|null $date The date (null or 'na' for no date)
 * @return string
 */
function get_year($date) {
    if ($date == null || $date == 'na')
        return null;
    $parse = date_parse($date);
    if ($parse['error_count'] > 0)
        return $date;
    $year = $parse['year'];
    if ($year != null && strlen($year) > 0)
        return $year;
    return $date;
}

/**
 * Get metadata, returning null if the metadata element is absent
 *
 * @param Record $item The item
 * @param string|array $key The metadata key
 * @param array $options Metadata options
 * @return The metadata values
 *
 * @see metadata
 */
function safe_metadata($item, $key, $options = null) {
    try {
        return strip_formatting(metadata($item, $key, $options));
    } catch (Omeka_View_Exception $ex) {
        return null;
    } catch (Omeka_Record_Exception $ex) {
        return null;
    }
}

/**
 * Add an element to a citation and return the constructed citation.
 * <p>
 * Elements that return null or emprty strings or
 *
 * @param string $citation The current citation string
 * @param Item item The
 * @param array|string $element The element name to add (null, empty or 'na') for none
 * @param string|null $begin A prefix to the element, if the element is included
 * @param string|null $end A suffix to the element, if the element is included
 * @param string|null $separator
 *
 * @return string
 */
function add_element($citation, $element, $begin = null, $end = null, $separator = ', ') {
    if ($element == null)
        return $citation;
    if (is_array($element))
        $element = join(', ', $element);
    $element = trim($element);
    if (strlen($element) == 0 || $element == 'na')
        return $citation;
    if ($separator && strlen($citation) > 0)
        $citation .= $separator;
    if ($begin)
        $citation .= $begin;
    $citation .= $element;
    if ($end)
        $citation .= $end;
    return $citation;
}

/**
 * Make an article citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_article_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $JOURNAL_ELEMENT, $PUBLISHER_ELEMENT, $LOCATION_ELEMENT, $VOLUME_ELEMENT, $NUMBER_ELEMENT, $PAGES_ELEMENT, $DOI_ELEMENT;

    $collection =  get_collection_for_item($item);
    $citation = add_element('', safe_metadata($item, $AUTHOR_ELEMENT));
    if (!$full)
        $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    else {
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)), null, null, ' ');
        $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), '\'', '\'');
        $journal = safe_metadata($item, $JOURNAL_ELEMENT);
        if (!$journal && $collection)
            $journal = safe_metadata($collection, $TITLE_ELEMENT);
        $citation = add_element($citation, $journal, $html ? '<em>' : null, $html ? '</em>' : null);
        $vnp = add_element('', safe_metadata($item, $VOLUME_ELEMENT));
        $vnp = add_element($vnp, safe_metadata($item, $NUMBER_ELEMENT), '(', ')', null);
        $vnp = add_element($vnp, safe_metadata($item, $PAGES_ELEMENT), null, null, ':');
        $citation = add_element($citation, $vnp);
        $publisher = safe_metadata($item, $PUBLISHER_ELEMENT);
        if (!$publisher && $collection)
            $publisher = safe_metadata($collection, $PUBLISHER_ELEMENT);
        $citation = add_element($citation, $publisher);
        $location = safe_metadata($item, $LOCATION_ELEMENT);
        if (!$location && $collection)
            $location = safe_metadata($collection, $LOCATION_ELEMENT);
        $citation = add_element($citation, $location);
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a conference paper citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_paper_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $BOOK_ELEMENT, $EDITOR_ELEMENT, $PUBLISHER_ELEMENT, $LOCATION_ELEMENT, $PAGES_ELEMENT, $DOI_ELEMENT;

    $collection =  get_collection_for_item($item);
    $citation = add_element('', safe_metadata($item, $AUTHOR_ELEMENT));
    if (!$full)
        $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    else {
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)), null, null, ' ');
        $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), '\'', '\'');
        $conference = safe_metadata($item, $BOOK_ELEMENT);
        if (!$conference && $collection)
            $conference = safe_metadata($collection, $TITLE_ELEMENT);
        $citation = add_element($citation, $conference, $html ? __('in') . ' <em>' : null, $html ? '</em>' : null);
        $editor = safe_metadata($item, $EDITOR_ELEMENT);
        if (!$editor && $collection)
            $editor = safe_metadata($collection, $AUTHOR_ELEMENT);
        $citation = add_element($citation, $editor, __('ed. '));
        $citation = add_element($citation, safe_metadata($item, $PAGES_ELEMENT));
        $publisher = safe_metadata($item, $PUBLISHER_ELEMENT);
        if (!$publisher && $collection)
            $publisher = safe_metadata($collection, $PUBLISHER_ELEMENT);
        $citation = add_element($citation, $publisher);
        $location = safe_metadata($item, $LOCATION_ELEMENT);
        if (!$location && $collection)
            $location = safe_metadata($collection, $LOCATION_ELEMENT);
        $citation = add_element($citation, $location);
        $citation = add_element($citation, safe_metadata($item, $DATE_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a book citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_book_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $EDITOR_ELEMENT, $VOLUME_ELEMENT, $PUBLISHER_ELEMENT, $LOCATION_ELEMENT, $PAGES_ELEMENT, $ISBN_ELEMENT;

    $citation = add_element('', safe_metadata($item, $AUTHOR_ELEMENT));
    if ($full)
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)), null, null, ' ');
    $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $VOLUME_ELEMENT), __('vol. '));
        $citation = add_element($citation, safe_metadata($item, $EDITOR_ELEMENT), __('ed. '));
        $citation = add_element($citation, safe_metadata($item, $PAGES_ELEMENT), __('pp. '));
        $citation = add_element($citation, safe_metadata($item, $PUBLISHER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $LOCATION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $ISBN_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a manual citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_manual_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $NUMBER_ELEMENT, $INSTITUTION_ELEMENT, $DOI_ELEMENT;

    $citation = '';
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $AUTHOR_ELEMENT));
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)), null, null, ' ');
    }
    $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $NUMBER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $INSTITUTION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a thesis citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_thesis_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $NUMBER_ELEMENT, $INSTITUTION_ELEMENT, $LOCATION_ELEMENT, $DOI_ELEMENT;

    $citation = add_element('', safe_metadata($item, $AUTHOR_ELEMENT));
    if (!$full)
        $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    else {
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)), null, null, ' ');
        $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>, thesis' : null);
        $citation = add_element($citation, safe_metadata($item, $NUMBER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $INSTITUTION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $LOCATION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make an report citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_report_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $NUMBER_ELEMENT, $INSTITUTION_ELEMENT, $DOI_ELEMENT;

    $citation = '';
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $AUTHOR_ELEMENT));
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)), null, null, ' ');
    }
    $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $NUMBER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $INSTITUTION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a generic text citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_text_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $PUBLISHER_ELEMENT, $DOI_ELEMENT;

    $citation = add_element('', safe_metadata($item, $AUTHOR_ELEMENT));
    $citation = add_element($citation, safe_metadata($TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $PUBLISHER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DATE_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a website citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_website_citation($item, $html, $full) {
    global $DATE_ELEMENT,  $TITLE_ELEMENT, $SOURCE_ELEMENT, $LOCAL_URL_ELEMENT;

    $citation = add_element("", safe_metadata($item, $TITLE_ELEMENT));
    if ($full) {
        $source = safe_metadata($item, $SOURCE_ELEMENT);
        $local = safe_metadata($item, $LOCAL_URL_ELEMENT);
        $citation = add_element($citation, $local ? $local : $source, $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
        $citation = add_element($citation, safe_metadata($item, $DATE_ELEMENT));
    }
    return $citation;
}

/**
 * Make an hyperlink citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_hyperlink_citation($item, $html, $full) {
    global $DATE_ELEMENT,  $TITLE_ELEMENT, $URL_ELEMENT;

    $citation = add_element("", safe_metadata($item, $TITLE_ELEMENT));
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $URL_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
        $citation = add_element($citation, safe_metadata($item, $DATE_ELEMENT), __('accessed '));
    }
    return $citation;
}

/**
 * Make a moving image citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_moving_image_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $DIRECTOR_ELEMENT, $PRODUCER_ELEMENT, $PUBLISHER_ELEMENT;

    $citation = add_element("", safe_metadata($item, $TITLE_ELEMENT));
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $AUTHOR_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DIRECTOR_ELEMENT), __('dir. '));
        $citation = add_element($citation, safe_metadata($item, $PRODUCER_ELEMENT), __('prod. '));
        $citation = add_element($citation, safe_metadata($item, $PUBLISHER_ELEMENT));
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)));
    }
    return $citation;
}

/**
 * Make a still image citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_still_image_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT, $TITLE_ELEMENT, $PUBLISHER_ELEMENT;

    $citation = add_element("", safe_metadata($item, $TITLE_ELEMENT));
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $AUTHOR_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $PUBLISHER_ELEMENT));
        $citation = add_element($citation, get_year(safe_metadata($item, $DATE_ELEMENT)));
    }
    return $citation;
}

/**
 * Make a generic citation
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_default_citation($item, $html, $full) {
    global $AUTHOR_ELEMENT, $DATE_ELEMENT,  $TITLE_ELEMENT, $PUBLISHER_ELEMENT, $NUMBER_ELEMENT, $INSTITUTION_ELEMENT, $LOCATION_ELEMENT, $PAGES_ELEMENT, $URL_ELEMENT, $DOI_ELEMENT;

    $citation = add_element("", safe_metadata($item, $AUTHOR_ELEMENT));
    $citation = add_element($citation, safe_metadata($item, $TITLE_ELEMENT), $html ? '<em>' : null, $html ? '</em>' : null);
    if ($full) {
        $citation = add_element($citation, safe_metadata($item, $NUMBER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $INSTITUTION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $PUBLISHER_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $LOCATION_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $PAGES_ELEMENT), 'pp. ');
        $citation = add_element($citation, safe_metadata($item, $URL_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
        $citation = add_element($citation, safe_metadata($item, $DATE_ELEMENT));
        $citation = add_element($citation, safe_metadata($item, $DOI_ELEMENT), $html ? '<span class="citation-url">' : null, $html ? '</span>' : null);
    }
    return $citation;
}

/**
 * Make a citation for an item.
 * <p>
 * The item type is used to determine what sort of citation should be generated.
 *
 * @param Item $item The item
 * @param boolean $html Use html formatting
 * @param boolean $full Full citation
 * @return string
 */
function make_citation($item, $html = true, $full = true) {
    $type = $item->getItemType();
    $type = $type ? $type->name : 'Unknown';
    if ($type == get_theme_option_with_default('Article Item Type', 'Article'))
        return make_article_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Conference Paper Type', 'Conference Paper'))
        return make_paper_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Book Item Type', 'Book'))
        return make_book_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Manual Item Type', 'Manual'))
        return make_manual_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Thesis Item Type', 'Thesis'))
        return make_thesis_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Report Item Type', 'Report'))
        return make_book_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Text Item Type', 'Text'))
        return make_book_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Website Item Type', 'Website'))
        return make_website_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Hyperlink Item Type', 'Hyperlink'))
        return make_hyperlink_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Moving Image Item Type', 'Moving Image'))
        return make_moving_image_citation($item, $html, $full);
    if ($type == get_theme_option_with_default('Still Image Item Type', 'Still Image'))
        return make_still_image_citation($item, $html, $full);
    return make_default_citation($item, $html, $full);
}

function make_caption($item)
{
    global $AUTHOR_ELEMENT, $TITLE_ELEMENT;
    $itemLabels = get_theme_option_with_default('item_labels', 'title_creator');

    switch ($itemLabels) {
    case 'title_creator':
    default:
        $caption = add_element('', safe_metadata($item, $TITLE_ELEMENT), '<em>', '</em>');
        $caption = add_element($caption, safe_metadata($item, $AUTHOR_ELEMENT));
        break;
    case 'creator_title':
        $caption = add_element('', safe_metadata($item, $AUTHOR_ELEMENT));
        $caption = add_element($caption, safe_metadata($item, $TITLE_ELEMENT), '<em>', '</em>');
        break;
    case 'title':
        $caption = safe_metadata($item, $TITLE_ELEMENT);
        break;
    }
    return $caption;
}

/**
 * Find an item that can be used as a 'hero shot'.
 * <p>
 * Featured collections are searched randomly until an item with an image is found.
 *
 * @return Item
 */
function get_random_hero_shot() {
    foreach (get_records('Collection', array('featured' => true, 'sort_field' => 'random')) as $collection) {
        foreach (get_records('Item', array('collection' => $collection->id, 'hasImage' => true, 'sort_field' => 'random')) as $item)
            return $item;
    }
    return null;
}

function bigstuff_sortable_items_shortcode($args, $view) {
    $sortable = isset($args['sortable']) ? $args['sortable'] : get_theme_option_with_default('sortable_shortcodes', '1');
    if (!$sortable)
        return Omeka_View_Helper_Shortcodes::shortcodeItems($args, $view);
    $sortLinks = array(
        __('Title') =>'Dublin Core,Title',
        __('Creator') => 'Dublin Core,Creator',
        __('Date') => 'Dublin Core,Date',
        __('Date Added') => 'added'
    );
    if (Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_field') !== null)
        $args['sort'] = Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_field');
    if (Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_dir') !== null)
        $args['order'] = Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_dir');
    $content = '<div class="item-shortcode">';
    $content .= '<div id="sort-links"><span class="sort-label">' . __('Sort by: ') . '</span>' . browse_sort_links($sortLinks) . '</div>';
    $content .= '<div class="item-list">';
    $content .= Omeka_View_Helper_Shortcodes::shortcodeItems($args, $view);
    $content .= '</div></div>';
    return $content;
}


function bigstuff_sortable_collections_shortcode($args, $view) {
    $sortable = isset($args['sortable']) ? $args['sortable'] : get_theme_option_with_default('sortable_shortcodes', '1');
    if (!$sortable)
        return Omeka_View_Helper_Shortcodes::shortcodeCollections($args, $view);
    $sortLinks = array(
        __('Title') =>'Dublin Core,Title',
        __('Creator') => 'Dublin Core,Creator',
        __('Date') => 'Dublin Core,Date',
        __('Date Added') => 'added'
    );
    if (Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_field') !== null)
        $args['sort'] = Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_field');
    if (Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_dir') !== null)
        $args['order'] = Zend_Controller_Front::getInstance()->getRequest()->getParam('sort_dir');
    $content = '<div class="item-shortcode">';
    $content .= '<div id="sort-links"><span class="sort-label">' . __('Sort by: ') . '</span>' . browse_sort_links($sortLinks) . '</div>';
    $content .= '<div class="item-list">';
    $content .= Omeka_View_Helper_Shortcodes::shortcodeCollections($args, $view);
    $content .= '</div></div>';
    return $content;
}
