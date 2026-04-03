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

/* modules/contrib/sitewide_alert/templates/sitewide-alert.html.twig */
class __TwigTemplate_5a164cfc081da3f731d40181fc3c678f extends Template
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
        // line 24
        yield "<div ";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
        yield ">
  ";
        // line 25
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 26
            yield "<span>";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content"] ?? null), "html", null, true);
            yield "</span>";
        }
        // line 28
        yield "  ";
        if ((($tmp = ($context["total_count"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 29
            yield "    <div class=\"alert-count\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("@current_count of @total_count alerts", ["@current_count" => ($context["current_count"] ?? null), "@total_count" => ($context["total_count"] ?? null)]));
            yield "</div>";
        }
        // line 31
        yield "  ";
        if ((($tmp = ($context["is_dismissible"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 33
            yield "    <button class=\"close js-dismiss-button\" aria-label=\"";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("close"));
            yield "\">
      <span aria-hidden=\"true\">×</span>
    </button>";
        }
        // line 37
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["attributes", "content", "total_count", "current_count", "is_dismissible"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/contrib/sitewide_alert/templates/sitewide-alert.html.twig";
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
        return array (  74 => 37,  67 => 33,  64 => 31,  59 => 29,  56 => 28,  51 => 26,  49 => 25,  44 => 24,);
    }

    public function getSourceContext(): Source
    {
        return new Source("{#
/**
 * @file sitewide_alert.html.twig
 * Default theme implementation to present Sitewide Alert data.
 *
 * This template is used when viewing Sitewide Alert messages.
 *
 *
 * Available variables:
 * - content: A list of content items. Use 'content' to print all content, or
 * - attributes: HTML attributes for the container element. This should contain the `data-uuid` attribute needed for
 *   the loading to work.
 * - uuid: The UUID of the sitewide alert.
 * - is_dismissible: True if this alert is dismissible, false otherwise.
 * - style: The alert style.
 * - style_class: A style class derived from the style.
 * - sitewide_alert: The sitewide alert entity.
 *
 * @see template_preprocess_sitewide_alert()
 *
 * @ingroup themeable
 */
#}
<div {{ attributes }}>
  {% if content -%}
    <span>{{- content -}}</span>
  {%- endif %}
  {% if total_count %}
    <div class=\"alert-count\">{{\"@current_count of @total_count alerts\"|t({\"@current_count\": current_count, \"@total_count\": total_count}) }}</div>
  {%- endif %}
  {% if is_dismissible -%}
  {# The dismiss (close) button must have the class js-dismiss-button in order to work. #}
    <button class=\"close js-dismiss-button\" aria-label=\"{{ 'close'|t }}\">
      <span aria-hidden=\"true\">×</span>
    </button>
  {%- endif %}
</div>
", "modules/contrib/sitewide_alert/templates/sitewide-alert.html.twig", "/var/www/html/gpps/web/modules/contrib/sitewide_alert/templates/sitewide-alert.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 25];
        static $filters = ["escape" => 24, "t" => 29];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape', 't'],
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
