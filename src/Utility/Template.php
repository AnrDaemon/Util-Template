<?php

/** "Simple PHP template" implementation
 *
 * Idea by {@see http://chadminick.com/articles/simple-php-template-engine.html Chad Minick }
 * Interface inspired by {@see https://www.smarty.net/ Smarty }.
 *
 * @version SVN: $Id: Template.php 1115 2022-12-04 20:30:44Z anrdaemon $
 */

namespace AnrDaemon\Utility;

class Template
{
  private $templateDir = null;
  private $template = null;
  private $params = [];
  private $result = null;

  private function protect(string $_template)
  {
    $store = array_diff_key($GLOBALS, ['GLOBALS' => null]);

    try
    {
      set_error_handler(
        function ($s, $m, $f, $l, $c = null)
        {
          throw new \ErrorException($m, 0, $s, $f, $l);
        },
        ~(E_NOTICE | E_STRICT)
      );

      if (!ob_start())
        throw new \UnexpectedValueException("Unable to start output buffering.");

      $depth = ob_get_level();
      $this->result = self::wrap($_template, $this->params);
    }
    finally
    {
      restore_error_handler();
      $GLOBALS = $store + ['GLOBALS' => &$GLOBALS];
    }

    if ($depth !== ob_get_level())
    {
      throw new \LogicException("Invalid output buffer depth.");
    }

    $result = ob_get_clean();
    return $result;
  }

  private static function wrap(string $_template, array $_vars)
  {
    extract($_vars, EXTR_SKIP);
    $_vars = isset($_vars["_vars"]) ? $_vars["_vars"] : null;

    return include $_template;
  }

  /** Assign variable to be used in the template
   *
   * @param iterable|string $vars Array of name-value pairs or a name for assigned variable.
   * @param ?mixed $value Value for an assigned variable, if first argument is a string.
   * @return static Chainable call.
   */
  public function assign($vars, $value = null)
  {
    if (is_scalar($vars))
    {
      $vars = [(string)$vars => $value];
    }

    if (!(is_array($vars) || is_object($vars) && $vars instanceof \Traversable))
    {
      throw new \BadMethodCallException("Dataset is not \\Traversable.");
    }

    foreach ($vars as $name => $value)
    {
      $this->params[$name] = $value;
    }

    return $this;
  }

  public function createTemplate(string $_template)
  {
    $self = clone $this;
    $self->template = $_template;

    return $self;
  }

  public function getTemplateDir()
  {
    return $this->templateDir;
  }

  public function getTemplateVars($name = null)
  {
    if (!isset($name))
      return $this->params;

    return $this->params[$name];
  }

  public function isCached()
  {
    return false;
  }

  public function display($_template = null)
  {
    print $this->fetch($_template);
  }

  protected function render($_template)
  {
    if (($_template[0] === "/" || $_template[0] === "\\") && !strlen($this->templateDir))
    {
      $_template = "/{$_template}";
    }

    return $this->protect("{$this->templateDir}/{$_template}");
  }

  public function fetch($_template = null)
  {
    if (!isset($_template))
    {
      $_template = $this->template;
    }

    if (!strlen($_template))
    {
      throw new \InvalidArgumentException("No template file specified.");
    }

    return $this->render($_template);
  }

  public function setTemplateDir($path)
  {
    if (empty($path) || !is_dir($path))
    {
      throw new \UnexpectedValueException("Templates path must be an existing directory.");
    }

    $this->templateDir = realpath($path);

    return $this;
  }

  // Magic!

  public function __construct()
  {
  }

  public function __get($name)
  {
    return $this->getTemplateVars($name);
  }

  public function __set($name, $value)
  {
    $this->assign($name, $value);
  }

  public function __isset($name)
  {
    return isset($this->params[$name]);
  }
}
