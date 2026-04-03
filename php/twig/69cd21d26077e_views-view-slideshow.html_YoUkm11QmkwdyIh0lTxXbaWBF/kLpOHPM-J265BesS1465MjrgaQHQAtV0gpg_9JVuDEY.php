<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* modules/contrib/views_slideshow/templates/views-view-slideshow.html.twig */
class __TwigTemplate_90a06e4e4316e565aebb769c0dc2a0b5 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 17
        if ((($tmp = ($context["slideshow"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 18
            yield "  <div class=\"skin-";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["skin"] ?? null), "html", null, true);
            yield "\">
    ";
            // line 19
            if ((($tmp = ($context["top_widget_rendered"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 20
                yield "      <div class=\"views-slideshow-controls-top clearfix\">
        ";
                // line 21
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["top_widget_rendered"] ?? null), "html", null, true);
                yield "
      </div>
    ";
            }
            // line 24
            yield "
    ";
            // line 25
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["slideshow"] ?? null), "html", null, true);
            yield "

    ";
            // line 27
            if ((($tmp = ($context["bottom_widget_rendered"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 28
                yield "      <div class=\"views-slideshow-controls-bottom clearfix\">
        ";
                // line 29
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["bottom_widget_rendered"] ?? null), "html", null, true);
                yield "
      </div>
    ";
            }
            // line 32
            yield "    </div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["slideshow", "skin", "top_widget_rendered", "bottom_widget_rendered"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/views_slideshow/templates/views-view-slideshow.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  81 => 32,  75 => 29,  72 => 28,  70 => 27,  65 => 25,  62 => 24,  56 => 21,  53 => 20,  51 => 19,  46 => 18,  44 => 17,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{#
/**
 * @file
 * Default theme implementation for a views slideshow.
 *
 * Available variables:
 * - bottom_widget_rendered: Widget under the slideshow with controls/data.
 * - skin: The skin being applied to the slideshow.
 * - slideshow: The slideshow.
 * - top_widget_rendered: Widget above the slideshow with controls/data.
 *
 * @see _views_slideshow_preprocess_views_view_slideshow()
 *
 * @ingroup vss_templates
 */
#}
{% if slideshow %}
  <div class=\"skin-{{ skin }}\">
    {% if top_widget_rendered %}
      <div class=\"views-slideshow-controls-top clearfix\">
        {{ top_widget_rendered }}
      </div>
    {% endif %}

    {{ slideshow }}

    {% if bottom_widget_rendered %}
      <div class=\"views-slideshow-controls-bottom clearfix\">
        {{ bottom_widget_rendered }}
      </div>
    {% endif %}
    </div>
{% endif %}
", "modules/contrib/views_slideshow/templates/views-view-slideshow.html.twig", "/var/www/html/gpps/web/modules/contrib/views_slideshow/templates/views-view-slideshow.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 17];
        static $filters = ["escape" => 18];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
