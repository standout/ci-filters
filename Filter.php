<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Filter library for CodeIgniter
 *
 * This class hold functionality to define and execute methods on per
 * controller basis.
 *
 * @author Kevin Sjöberg
 * @link   http://ksjoberg.com
 */
class Filter
{
  protected $CI;
  protected $_filters = array();
  protected $_option_keys = array('except', 'only');

  /**
   * Class constructor
   *
   * Fetch the CI instance by reference in order to access loaded resources.
   */
  public function __construct()
  {
    $this->CI =& get_instance();
  }

  /**
   * Run
   *
   * This will iterate over available filters and evaluate their conditions.
   * Each filter for which conditions passed will then be called on the CI
   * object. The CI object will be the current class during execution.
   */
  public function run()
  {
    // Return early if no filters found.
    if (sizeof($this->_filters) === 0) return TRUE;

    foreach ($this->_filters as $filter => $opts)
    {
      // Check whether the current filter have a conditions or not. If not,
      // continue, otherwise evaluate the conditions.
      switch ($opts['condition'])
      {
        case NULL:
          if ($opts['skip'])
            continue 2;
          break;
        case 'except':
          if($opts['skip'] && ! $this->method_match($opts['methods']))
            continue 2;
          if( ! $opts['skip'] && $this->method_match($opts['methods']))
            continue 2;
          break;
        case 'only':
          if($opts['skip'] && $this->method_match($opts['methods']))
            continue 2;
          if( ! $opts['skip'] && ! $this->method_match($opts['methods']))
            continue 2;
          break;
      }

      $this->CI->$filter();
    }
  }

  /**
   * Apply
   *
   * Apply one or more filters with or without options.
   */
  public function apply($filters, $options = array())
  {
    $options = $this->prepare_options($options);

    // Store given filters with given options.
    foreach ((array) $filters as $filter)
    {
      // Exit early if the current filter isn't found.
      if ( ! method_exists($this->CI, $filter))
      {
        show_error(
        "Unable to run filter {$filter}.
         Filter does not exist.
        ");
      }

      $this->_filters[$filter] = $options;
    }
  }

  /**
   * Skip
   *
   * Skip one ore more filters with our without options.
   */
  public function skip($filters, $options = array())
  {
    $options = $this->prepare_options($options, TRUE);

    foreach ((array) $filters as $filter)
    {
      // Exit early in case of a skip reference to an unset filter.
      // You can only skip filters that you have applied.
      if ( ! isset($this->_filters[$filter]))
      {
        show_error(
        "Unable to skip filter {$filter}.
         Filter have not been applied.
        ");
      }

      $this->_filters[$filter] = $options;
    }
  }

  /**
   * Prepare options
   *
   * This will parse and prepare options for condition handling.
   */
  private function prepare_options($options, $skip = FALSE)
  {
    $option_keys = array_keys($options);

    if ($this->valid_option_keys($option_keys))
    {
      $_options = array(
        'condition' => NULL,
        'methods'   => NULL,
        'skip'      => $skip
      );

      if ( ! empty($options))
      {
        $_options['condition'] = $option_keys[0];
        $_options['methods']   = (array) $options[$_options['condition']];
      }

      return $_options;
    }
  }

  /**
   * Valid options keys
   *
   * Validates given options when applying or skipping filters. Throws an error
   * if filter applied/skipped with invalid options.
   */
  private function valid_option_keys($keys)
  {
    foreach ($keys as $key)
    {
      if ( ! in_array($key, $this->_option_keys))
      {
        show_error(
        "Filter applied with invalid options. ".join(',', $keys).
        " isn't valid option keys.
        ");
      }
    }

    if (in_array('except', $keys) && in_array('only', $keys))
    {
      show_error(
      "Filter applied with invalid options. 'except' and 'only' can not be
       combined.
      ");
    }

    return TRUE;
  }

  /**
   * Method match
   *
   * Convenience method to match the current Router method against given
   * methods.
   */
  private function method_match($methods)
  {
    return in_array($this->CI->router->method, $methods);
  }
}
