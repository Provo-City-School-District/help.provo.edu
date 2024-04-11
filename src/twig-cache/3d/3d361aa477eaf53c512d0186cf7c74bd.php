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

/* profile.phtml */
class __TwigTemplate_bccd9e98f775e19947168e05cd5c6d0a extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "base.phtml";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("base.phtml", "profile.phtml", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo "<h1> Profile For ";
        echo twig_escape_filter($this->env, ($context["first_name"] ?? null), "html", null, true);
        echo " ";
        echo twig_escape_filter($this->env, ($context["last_name"] ?? null), "html", null, true);
        echo " (";
        echo twig_escape_filter($this->env, ($context["username"] ?? null), "html", null, true);
        echo ") </h1>

<h2>My Information</h2>
<ul>
    <li>Name: ";
        // line 8
        echo twig_escape_filter($this->env, ($context["first_name"] ?? null), "html", null, true);
        echo " ";
        echo twig_escape_filter($this->env, ($context["last_name"] ?? null), "html", null, true);
        echo "</li>
    <li>Email: ";
        // line 9
        echo twig_escape_filter($this->env, ($context["email"] ?? null), "html", null, true);
        echo "</li>
    <li>Employee ID: ";
        // line 10
        echo twig_escape_filter($this->env, ($context["employee_id"] ?? null), "html", null, true);
        echo "</li>
</ul>

<h2>My Settings</h2>
<form action=\"/controllers/users/update_user_settings.php\" method=\"post\" class=\"singleColForm\">
    <!-- Controller Variables -->
    <input type=\"hidden\" name=\"id\" value=\"";
        // line 16
        echo twig_escape_filter($this->env, ($context["username"] ?? null), "html", null, true);
        echo "\">
    <input type=\"hidden\" name=\"referer\" value=\"profile.php\">
    <!-- User Options -->
    <div>
        <label for=\"color_scheme\">Color Scheme:</label>
        <select id=\"color_scheme\" name=\"color_scheme\">
            ";
        // line 22
        if ((($context["current_color_scheme"] ?? null) == "system")) {
            // line 23
            echo "            <option value=\"system\" selected=\"selected\">System Select</option>
            <option value=\"dark\">Dark Mode</option>
            <option value=\"light\">Light Mode</option>
            ";
        } elseif ((        // line 26
($context["current_color_scheme"] ?? null) == "dark")) {
            // line 27
            echo "            <option value=\"system\">System Select</option>
            <option value=\"dark\" selected=\"selected\">Dark Mode</option>
            <option value=\"light\">Light Mode</option>
            ";
        } elseif ((        // line 30
($context["current_color_scheme"] ?? null) == "light")) {
            // line 31
            echo "            <option value=\"system\">System Select</option>
            <option value=\"dark\" >Dark Mode</option>
            <option value=\"light\" selected=\"selected\">Light Mode</option>
            ";
        }
        // line 35
        echo "        </select>
    </div>
    <div>
        <label for=\"note_order\">Ticket Note Order:</label>
        <select id=\"note_order\" name=\"note_order\">
            ";
        // line 40
        if ((($context["note_order"] ?? null) == "ASC")) {
            // line 41
            echo "            <option value=\"ASC\" selected=\"selected\">Ascending</option>
            <option value=\"DESC\">Descending</option>
            ";
        } else {
            // line 44
            echo "            <option value=\"ASC\">Ascending</option>
            <option value=\"DESC\" selected=\"selected\">Descending</option>
            ";
        }
        // line 47
        echo "        </select>
    </div>
    <div>
        <label for=\"hide_alerts\">Hide Alerts Banner on \"My Tickets\" Page:</label>
        ";
        // line 51
        if (($context["hide_alerts"] ?? null)) {
            // line 52
            echo "        <input type=\"checkbox\" id=\"hide_alerts\" name=\"hide_alerts\" checked=\"checked\">
        ";
        } else {
            // line 54
            echo "        <input type=\"checkbox\" id=\"hide_alerts\" name=\"hide_alerts\">
        ";
        }
        // line 56
        echo "    </div>

    <input type=\"submit\" value=\"Update\">
</form>
<h2>Help / Documentation</h2>
<a href=\"/note_shortcuts.php\">Note Shorthand</a>
";
        // line 62
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_tech", [], "any", false, false, false, 62)) {
            // line 63
            echo "<h2>Current Week Work Order Hours</h2>
    <table id=\"profile_time_table\">
        <tr>
            <th>Monday</th>
            <th>Tuesday</th>
            <th>Wednesday</th>
            <th>Thursday</th>
            <th>Friday</th>
            <th>Total</th>
        </tr>
        <tr>
            <td data-cell=\"Monday\">";
            // line 74
            echo twig_escape_filter($this->env, (($__internal_compile_0 = ($context["user_times"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[0] ?? null) : null), "html", null, true);
            echo " hrs</td>
            <td data-cell=\"Tuesday\">";
            // line 75
            echo twig_escape_filter($this->env, (($__internal_compile_1 = ($context["user_times"] ?? null)) && is_array($__internal_compile_1) || $__internal_compile_1 instanceof ArrayAccess ? ($__internal_compile_1[1] ?? null) : null), "html", null, true);
            echo " hrs</td>
            <td data-cell=\"Wednesday\">";
            // line 76
            echo twig_escape_filter($this->env, (($__internal_compile_2 = ($context["user_times"] ?? null)) && is_array($__internal_compile_2) || $__internal_compile_2 instanceof ArrayAccess ? ($__internal_compile_2[2] ?? null) : null), "html", null, true);
            echo " hrs</td>
            <td data-cell=\"Thursday\">";
            // line 77
            echo twig_escape_filter($this->env, (($__internal_compile_3 = ($context["user_times"] ?? null)) && is_array($__internal_compile_3) || $__internal_compile_3 instanceof ArrayAccess ? ($__internal_compile_3[3] ?? null) : null), "html", null, true);
            echo " hrs</td>
            <td data-cell=\"Friday\">";
            // line 78
            echo twig_escape_filter($this->env, (($__internal_compile_4 = ($context["user_times"] ?? null)) && is_array($__internal_compile_4) || $__internal_compile_4 instanceof ArrayAccess ? ($__internal_compile_4[4] ?? null) : null), "html", null, true);
            echo " hrs</td>
            <td data-cell=\"Week Total\">";
            // line 79
            echo twig_escape_filter($this->env, ($context["user_time_total"] ?? null), "html", null, true);
            echo " hrs</td>
        </tr>
    </table>
";
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "profile.phtml";
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
        return array (  190 => 79,  186 => 78,  182 => 77,  178 => 76,  174 => 75,  170 => 74,  157 => 63,  155 => 62,  147 => 56,  143 => 54,  139 => 52,  137 => 51,  131 => 47,  126 => 44,  121 => 41,  119 => 40,  112 => 35,  106 => 31,  104 => 30,  99 => 27,  97 => 26,  92 => 23,  90 => 22,  81 => 16,  72 => 10,  68 => 9,  62 => 8,  50 => 4,  46 => 3,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "profile.phtml", "/var/www/html/views/profile.phtml");
    }
}
