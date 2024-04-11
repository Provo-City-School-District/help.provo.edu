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

/* base.phtml */
class __TwigTemplate_20d3adb71a648e03a404066a27e2a526 extends Template
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
        // line 17
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
        // line 27
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_supervisor", [], "any", false, false, false, 27)) {
            // line 28
            echo "                        <a href=\"/supervisor.php\">Supervisor</a>
                    ";
        }
        // line 30
        echo "
                    ";
        // line 31
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_admin", [], "any", false, false, false, 31)) {
            // line 32
            echo "                        <a href=\"/admin.php\">Admin</a>
                    ";
        }
        // line 34
        echo "
                    <a href=\"/controllers/logout.php\">Logout</a>
                </nav>
                <div id=\"dayWOHours\">
                    Today's WO time: ";
        // line 38
        echo twig_escape_filter($this->env, ($context["wo_time"] ?? null), "html", null, true);
        echo " hrs
                </div>
            </header>
            <div id=\"content\">
                ";
        // line 42
        $this->displayBlock('content', $context, $blocks);
        // line 45
        echo "            </div>
            <div id=\"footer\">
                ";
        // line 47
        $this->displayBlock('footer', $context, $blocks);
        // line 50
        echo "            </div>
        </div>
        <script>
            const userPref = \"";
        // line 53
        echo twig_escape_filter($this->env, ($context["user_pref"] ?? null), "html", null, true);
        echo "\";
            const ticketLimit = \"";
        // line 54
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

            ";
        // line 15
        echo "            <link rel=\"stylesheet\" type=\"text/css\" href=\"/includes/css/variables-";
        echo twig_escape_filter($this->env, ($context["color_scheme"] ?? null), "html", null, true);
        echo ".css\">
        ";
    }

    // line 42
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 43
        echo "
                ";
    }

    // line 47
    public function block_footer($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 48
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
        return "base.phtml";
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
        return array (  155 => 48,  151 => 47,  146 => 43,  142 => 42,  135 => 15,  124 => 5,  120 => 4,  104 => 54,  100 => 53,  95 => 50,  93 => 47,  89 => 45,  87 => 42,  80 => 38,  74 => 34,  70 => 32,  68 => 31,  65 => 30,  61 => 28,  59 => 27,  47 => 17,  45 => 4,  40 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "base.phtml", "/var/www/html/views/base.phtml");
    }
}
