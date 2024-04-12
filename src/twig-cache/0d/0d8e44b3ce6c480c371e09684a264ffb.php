<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* base.twig */
class __TwigTemplate_7f19428e86aee5af976a997339f0eecf extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
            'head' => [$this, 'block_head'],
            'menu' => [$this, 'block_menu'],
            'content' => [$this, 'block_content'],
            'footer' => [$this, 'block_footer'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!DOCTYPE html>
<html>
    <head>
        ";
        // line 4
        $this->displayBlock('head', $context, $blocks);
        // line 15
        echo "    </head>
    <body>
        <div id=\"wrapper\">
            <header id=\"mainHeader\">
                <a href=\"/tickets.php\">
                    <img id=\"pcsd-logo\" src=\"/includes/img/pcsd-logo-website-header-160w.png\" alt=\"Provo City School District Logo\" />
                </a>
                <nav id=\"headerNav\">
                    <a href=\"/profile.php\">Profile</a>
                    <a href=\"/tickets.php\">Tickets</a>
                    ";
        // line 25
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_supervisor", [], "any", false, false, false, 25)) {
            // line 26
            echo "                        <a href=\"/supervisor.php\">Supervisor</a>
                    ";
        }
        // line 28
        echo "
                    ";
        // line 29
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_admin", [], "any", false, false, false, 29)) {
            // line 30
            echo "                        <a href=\"/admin.php\">Admin</a>
                    ";
        }
        // line 32
        echo "
                    <a href=\"/controllers/logout.php\">Logout</a>
                </nav>
                <div id=\"dayWOHours\">
                    Today's WO time: ";
        // line 36
        echo twig_escape_filter($this->env, ($context["wo_time"] ?? null), "html", null, true);
        echo " hrs
                </div>
            </header>
\t\t\t";
        // line 39
        if ( !(null === ($context["status_alert_message"] ?? null))) {
            // line 40
            echo "\t\t\t<link rel=\"stylesheet\" href=\"/includes/css/status_popup.css?v=1.0.0\">
\t\t\t<div class=\"ticket-status-notification\">
\t\t\t\t<div class=\"alert ";
            // line 42
            echo twig_escape_filter($this->env, ($context["status_alert_type"] ?? null), "html", null, true);
            echo "\">
\t\t\t\t<span class=\"alertText\"><b>";
            // line 43
            echo twig_escape_filter($this->env, twig_capitalize_string_filter($this->env, ($context["status_alert_type"] ?? null)), "html", null, true);
            echo ":</b> ";
            echo twig_escape_filter($this->env, ($context["status_alert_message"] ?? null), "html", null, true);
            echo "</span>
\t\t\t\t</div>
\t\t\t</div>
\t\t\t";
        }
        // line 47
        echo "            <main id=\"pageContent\">
                ";
        // line 48
        $this->displayBlock('menu', $context, $blocks);
        // line 51
        echo "                ";
        $this->displayBlock('content', $context, $blocks);
        // line 54
        echo "            </main>
            <div id=\"footer\">
                ";
        // line 56
        $this->displayBlock('footer', $context, $blocks);
        // line 59
        echo "            </div>
        </div>
        <script>
            const userPref = \"";
        // line 62
        echo twig_escape_filter($this->env, ($context["user_pref"] ?? null), "html", null, true);
        echo "\";
            const ticketLimit = \"";
        // line 63
        echo twig_escape_filter($this->env, ($context["ticket_limit"] ?? null), "html", null, true);
        echo "\";
        </script>
        <script src=\"/includes/js/jquery-3.7.1.min.js\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/dataTables-1.13.7/jquery.dataTables.min.js\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/jquery-ui.min.js\" type=\"text/javascript\"></script>
        <script src=\"https://cdn.canvasjs.com/canvasjs.min.js\"></script>
        <script src=\"/vendor/tinymce/tinymce/tinymce.min.js\" referrerpolicy=\"origin\"></script>
        <script src=\"/includes/js/tinyMCE-conf.js?v=1.0.0\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/dataTables-conf.js?v=1.0.01\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/global.js?v=1\" type=\"text/javascript\"></script>
    </body>
</html>";
    }

    // line 4
    public function block_head($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 5
        echo "            <title>Help For Provo City School District</title>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <meta http-equiv=\"refresh\" content=\"3600\">
            <link rel=\"stylesheet\" href=\"/includes/js/dataTables-1.13.7/jquery.dataTables.min.css\">
            <link rel=\"stylesheet\" href=\"/includes/css/main.css\">
            <link rel=\"icon\" type=\"image/png\" href=\"/includes/img/favicons/favicon-16x16.png\" sizes=\"16x16\">
            <link rel=\"stylesheet\" href=\"/includes/css/jquery-ui.min.css\">
            <link rel=\"stylesheet\" type=\"text/css\" href=\"/includes/css/variables-";
        // line 13
        echo twig_escape_filter($this->env, ($context["color_scheme"] ?? null), "html", null, true);
        echo ".css\">
        ";
    }

    // line 48
    public function block_menu($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 49
        echo "                
                ";
    }

    // line 51
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 52
        echo "
                ";
    }

    // line 56
    public function block_footer($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 57
        echo "                    <p>&copy; 2023 - ";
        echo twig_escape_filter($this->env, ($context["current_year"] ?? null), "html", null, true);
        echo " Provo City School District | <a href=\"https://provo.edu/helpdesk-feedback-form/\">Help us Improve our Helpdesk</a></p>
                ";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "base.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  187 => 57,  183 => 56,  178 => 52,  174 => 51,  169 => 49,  165 => 48,  159 => 13,  149 => 5,  145 => 4,  129 => 63,  125 => 62,  120 => 59,  118 => 56,  114 => 54,  111 => 51,  109 => 48,  106 => 47,  97 => 43,  93 => 42,  89 => 40,  87 => 39,  81 => 36,  75 => 32,  71 => 30,  69 => 29,  66 => 28,  62 => 26,  60 => 25,  48 => 15,  46 => 4,  41 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "base.twig", "/var/www/html/views/base.twig");
    }
}
