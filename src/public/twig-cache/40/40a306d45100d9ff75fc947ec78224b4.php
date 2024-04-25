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

/* ticket_table_base.twig */
class __TwigTemplate_0546366e27f7d405d264114b304cf9e6 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
            'page_title' => [$this, 'block_page_title'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "ticket_base.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("ticket_base.twig", "ticket_table_base.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        if (((array_key_exists("alerts", $context) && twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_tech", [], "any", false, false, false, 4)) &&  !($context["hide_alerts"] ?? null))) {
            // line 5
            echo "<div class=\"alerts_wrapper\">
\t";
            // line 6
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["alerts"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["alert"]) {
                // line 7
                echo "\t<p class=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["alert"], "alert_level", [], "any", false, false, false, 7), "html", null, true);
                echo "\">
\t<a href=\"/controllers/tickets/edit_ticket.php?id=";
                // line 8
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["alert"], "ticket_id", [], "any", false, false, false, 8), "html", null, true);
                echo "\">
\t\tTicket: ";
                // line 9
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["alert"], "ticket_id", [], "any", false, false, false, 9), "html", null, true);
                echo " - ";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["alert"], "message", [], "any", false, false, false, 9), "html", null, true);
                echo "
\t</a>
\t<a class=\"close-alert\" href=\"/controllers/tickets/alert_delete.php?id=";
                // line 11
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["alert"], "id", [], "any", false, false, false, 11), "html", null, true);
                echo "\">&times;</a>
\t";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['alert'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 13
            echo "</div>
";
        }
        // line 15
        $this->displayBlock('page_title', $context, $blocks);
        // line 18
        echo "<table class=\"ticketsTable data-table\">
\t<thead>
\t\t<tr>
\t\t\t<th class=\"tID\">ID</th>
\t\t\t<th class=\"reqDetail\">Request Detail</th>
\t\t\t<th class=\"tLatestNote\">Latest Note</th>
\t\t\t<th class=\"client\">Client</th>
\t\t\t<th class=\"tLocation\">Location</th>
\t\t\t<th class=\"category\">Request Category</th>
\t\t\t<th class=\"status\">Current Status</th>
\t\t\t<th class=\"priority\">Priority</th>
\t\t\t<th class=\"tDate\">Created Date</th>
\t\t\t<th class=\"tDate\">Last Updated</th>
\t\t\t<th class=\"date\">Due</th>
\t\t\t<th class=\"curAssign\">Assigned</th>
\t\t\t<th class=\"alertLevel\">Alert</th>
\t\t</tr>
\t</thead>
\t<tbody>
\t\t";
        // line 37
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["tickets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["ticket"]) {
            // line 38
            echo "\t\t<tr class=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "row_color", [], "any", false, false, false, 38), "html", null, true);
            echo "\">
\t\t\t<td data-cell=\"ID\"><a href=\"/controllers/tickets/edit_ticket.php?id=";
            // line 39
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 39), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 39), "html", null, true);
            echo "</a></td>
\t\t\t<td class=\"details\" data-cell=\"Request Detail\"><a href=\"/controllers/tickets/edit_ticket.php?id=";
            // line 40
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 40), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "title", [], "any", false, false, false, 40), "html", null, true);
            echo "</a>";
            echo twig_get_attribute($this->env, $this->source, $context["ticket"], "description", [], "any", false, false, false, 40);
            echo "</td>
\t\t\t";
            // line 41
            if ((twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", true, true, false, 41) &&  !(null === twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", false, false, false, 41)))) {
                // line 42
                echo "\t\t\t\t<td class=\"latestNote\" data-cell=\"Latest Note:\"><strong>";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note_author", [], "any", false, false, false, 42), "html", null, true);
                echo ": </strong> ";
                echo twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", false, false, false, 42);
                echo "</td>
\t\t\t";
            } else {
                // line 44
                echo "\t\t\t\t<td class=\"latestNote\" data-cell=\"Latest Note:\"></td>
\t\t\t";
            }
            // line 46
            echo "\t\t\t<td data-cell=\"Client: \">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_first_name", [], "any", false, false, false, 46), "html", null, true);
            echo " ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_last_name", [], "any", false, false, false, 46), "html", null, true);
            echo " (";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_username", [], "any", false, false, false, 46), "html", null, true);
            echo ")</td>
\t\t\t<td data-cell=\"Location\">
\t\t\t\t";
            // line 48
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "location_name", [], "any", false, false, false, 48), "html", null, true);
            echo "
\t\t\t\t";
            // line 49
            if (((twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", true, true, false, 49) &&  !(null === twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 49))) &&  !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 49)))) {
                // line 50
                echo "\t\t\t\t\t<br><br>
\t\t\t\t\tRM ";
                // line 51
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 51), "html", null, true);
                echo "
\t\t\t\t";
            }
            // line 53
            echo "\t\t\t</td>
\t\t\t<td data-cell=\"Request Category\">";
            // line 54
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "request_category", [], "any", false, false, false, 54), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Current Status\">";
            // line 55
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "status", [], "any", false, false, false, 55), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Priority\">
\t\t\t\t<span class=\"sort-value\">";
            // line 57
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "sort_value", [], "any", false, false, false, 57), "html", null, true);
            echo "</span>
\t\t\t\t";
            // line 58
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "priority", [], "any", false, false, false, 58), "html", null, true);
            echo "
\t\t\t</td>
\t\t\t<td data-cell=\"Created\">";
            // line 60
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "created", [], "any", false, false, false, 60), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Last Updated\">";
            // line 61
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "last_updated", [], "any", false, false, false, 61), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Due\">";
            // line 62
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "due_date", [], "any", false, false, false, 62), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Assigned\">";
            // line 63
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "assigned_tech", [], "any", false, false, false, 63), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Alert Levels\">";
            // line 64
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "alert_level", [], "any", false, false, false, 64), "html", null, true);
            echo "</td>
\t\t</tr>
\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['ticket'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 67
        echo "\t</tbody>
</table>
";
    }

    // line 15
    public function block_page_title($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 16
        echo "
";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "ticket_table_base.twig";
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
        return array (  228 => 16,  224 => 15,  218 => 67,  209 => 64,  205 => 63,  201 => 62,  197 => 61,  193 => 60,  188 => 58,  184 => 57,  179 => 55,  175 => 54,  172 => 53,  167 => 51,  164 => 50,  162 => 49,  158 => 48,  148 => 46,  144 => 44,  136 => 42,  134 => 41,  126 => 40,  120 => 39,  115 => 38,  111 => 37,  90 => 18,  88 => 15,  84 => 13,  76 => 11,  69 => 9,  65 => 8,  60 => 7,  56 => 6,  53 => 5,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "ticket_table_base.twig", "/var/www/html/public/views/ticket_table_base.twig");
    }
}
