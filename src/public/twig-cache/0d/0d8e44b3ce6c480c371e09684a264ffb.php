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
<html lang=\"en\">
    <head>
        ";
        // line 4
        $this->displayBlock('head', $context, $blocks);
        // line 16
        echo "    </head>
    <body>
        <div id=\"wrapper\">
            <header id=\"mainHeader\">
                <a href=\"/tickets.php\">
                    <img id=\"pcsd-logo\" src=\"/includes/img/pcsd-logo-website-header-160w.png\" alt=\"Provo City School District Logo\" />
                </a>
                
                <div id=\"headerNav\">
                    <span id=\"mobileMenu\">&#9776; Menu</span>
                    <nav id=\"mainNav\">
                        <a href=\"/profile.php\">Profile</a>
                        <a href=\"/tickets.php\">Tickets</a>
                    ";
        // line 29
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_supervisor", [], "any", false, false, false, 29)) {
            // line 30
            echo "                        <a href=\"/supervisor.php\">Supervisor</a>
                    ";
        }
        // line 32
        echo "

                    ";
        // line 34
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_admin", [], "any", false, false, false, 34)) {
            // line 35
            echo "                        <a href=\"/admin.php\">Admin</a>
                    ";
        }
        // line 37
        echo "                        <a href=\"/controllers/logout.php\">Logout</a>
                    </nav>

                </div>
                <div id=\"dayWOHours\">
                    <div>Today's WO time:</div> 
\t\t\t\t\t";
        // line 43
        echo twig_escape_filter($this->env, ($context["wo_time"] ?? null), "html", null, true);
        echo " hrs
                </div>
            </header>
\t\t\t";
        // line 46
        if ( !(null === ($context["status_alert_message"] ?? null))) {
            // line 47
            echo "\t\t\t<link rel=\"stylesheet\" href=\"/includes/css/status_popup.css?v=";
            echo twig_escape_filter($this->env, ($context["app_version"] ?? null), "html", null, true);
            echo "\">
\t\t\t<div class=\"ticket-status-notification\">
\t\t\t\t<div class=\"alert ";
            // line 49
            echo twig_escape_filter($this->env, ($context["status_alert_type"] ?? null), "html", null, true);
            echo "\">
\t\t\t\t<span class=\"alertText\"><b>";
            // line 50
            echo twig_escape_filter($this->env, twig_capitalize_string_filter($this->env, ($context["status_alert_type"] ?? null)), "html", null, true);
            echo ":</b> ";
            echo twig_escape_filter($this->env, ($context["status_alert_message"] ?? null), "html", null, true);
            echo "</span>
\t\t\t\t</div>
\t\t\t</div>
\t\t\t";
        }
        // line 54
        echo "            <main id=\"pageContent\">
                ";
        // line 55
        $this->displayBlock('menu', $context, $blocks);
        // line 58
        echo "                ";
        $this->displayBlock('content', $context, $blocks);
        // line 61
        echo "            </main>
            <div id=\"mainFooter\">
                ";
        // line 63
        $this->displayBlock('footer', $context, $blocks);
        // line 66
        echo "            </div>
        </div>
        <script>
            const userPref = \"";
        // line 69
        echo twig_escape_filter($this->env, ($context["user_pref"] ?? null), "html", null, true);
        echo "\";
            const ticketLimit = \"";
        // line 70
        echo twig_escape_filter($this->env, ($context["ticket_limit"] ?? null), "html", null, true);
        echo "\";
        </script>
        <script src=\"/includes/js/jquery-3.7.1.min.js\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/dataTables-2.0.5/jquery.dataTables.min.js\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/jquery-ui.min.js\" type=\"text/javascript\"></script>
        <script src=\"https://cdn.canvasjs.com/canvasjs.min.js\"></script>
        <script src=\"/vendor/tinymce/tinymce/tinymce.min.js\" referrerpolicy=\"origin\"></script>
        <script src=\"/includes/js/tinyMCE-conf.js?v=";
        // line 77
        echo twig_escape_filter($this->env, ($context["app_version"] ?? null), "html", null, true);
        echo "\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/dataTables-conf.js?v=";
        // line 78
        echo twig_escape_filter($this->env, ($context["app_version"] ?? null), "html", null, true);
        echo "\" type=\"text/javascript\"></script>
        <script src=\"/includes/js/global.js?v=";
        // line 79
        echo twig_escape_filter($this->env, ($context["app_version"] ?? null), "html", null, true);
        echo "\" type=\"text/javascript\"></script>
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
            <link rel=\"stylesheet\" href=\"/includes/js/dataTables-2.0.5/jquery.dataTables.min.css\">
            <link rel=\"stylesheet\" href=\"/includes/css/main.css?v=";
        // line 10
        echo twig_escape_filter($this->env, ($context["app_version"] ?? null), "html", null, true);
        echo "\">
            <link rel=\"icon\" type=\"image/png\" href=\"/includes/img/favicons/favicon-16x16.png\" sizes=\"16x16\">
            <link rel=\"stylesheet\" href=\"/includes/css/jquery-ui.min.css\">
            <link rel=\"stylesheet\" type=\"text/css\" href=\"/includes/css/variables-";
        // line 13
        echo twig_escape_filter($this->env, ($context["color_scheme"] ?? null), "html", null, true);
        echo ".css?v=";
        echo twig_escape_filter($this->env, ($context["app_version"] ?? null), "html", null, true);
        echo "\">
           
        ";
    }

    // line 55
    public function block_menu($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 56
        echo "                
                ";
    }

    // line 58
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 59
        echo "
                ";
    }

    // line 63
    public function block_footer($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 64
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
        return array (  210 => 64,  206 => 63,  201 => 59,  197 => 58,  192 => 56,  188 => 55,  179 => 13,  173 => 10,  166 => 5,  162 => 4,  155 => 79,  151 => 78,  147 => 77,  137 => 70,  133 => 69,  128 => 66,  126 => 63,  122 => 61,  119 => 58,  117 => 55,  114 => 54,  105 => 50,  101 => 49,  95 => 47,  93 => 46,  87 => 43,  79 => 37,  75 => 35,  73 => 34,  69 => 32,  65 => 30,  63 => 29,  48 => 16,  46 => 4,  41 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "base.twig", "/var/www/html/public/views/base.twig");
    }
}
