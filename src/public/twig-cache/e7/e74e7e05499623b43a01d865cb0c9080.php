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

/* tickets.twig */
class __TwigTemplate_dd586692ac4d0da53d66fc071e1987c1 extends Template
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
        return "ticket_base.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("ticket_base.twig", "tickets.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        if ((twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_tech", [], "any", false, false, false, 4) &&  !($context["hide_alerts"] ?? null))) {
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
        echo "<h1>My Assigned Tickets</h1>
<table class=\"ticketsTable data-table\">
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
\t\t\t<th class=\"\">Assigned</th>
\t\t\t<th class=\"alertLevel\">Alert</th>
\t\t</tr>
\t</thead>
\t<tbody>
\t\t";
        // line 35
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["my_tickets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["ticket"]) {
            // line 36
            echo "\t\t<tr class=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "row_color", [], "any", false, false, false, 36), "html", null, true);
            echo "\">
\t\t\t<td data-cell=\"ID\"><a href=\"/controllers/tickets/edit_ticket.php?id=";
            // line 37
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 37), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 37), "html", null, true);
            echo "</a></td>
\t\t\t<td class=\"details\" data-cell=\"Request Detail\"><a href=\"/controllers/tickets/edit_ticket.php?id=";
            // line 38
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 38), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_replace_filter(twig_get_attribute($this->env, $this->source, $context["ticket"], "title", [], "any", false, false, false, 38), ["&quot;" => "\"", "&#039;" => "'", "&#39;" => "'"]), "html", null, true);
            echo "</a>";
            echo twig_get_attribute($this->env, $this->source, $context["ticket"], "description", [], "any", false, false, false, 38);
            echo "</td>
\t\t\t";
            // line 39
            if ((twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", true, true, false, 39) &&  !(null === twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", false, false, false, 39)))) {
                // line 40
                echo "\t\t\t\t<td class=\"latestNote\" data-cell=\"Latest Note:\"><strong>";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note_author", [], "any", false, false, false, 40), "html", null, true);
                echo ": </strong> ";
                echo twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", false, false, false, 40);
                echo "</td>
\t\t\t";
            } else {
                // line 42
                echo "\t\t\t\t<td class=\"latestNote\" data-cell=\"Latest Note:\"></td>
\t\t\t";
            }
            // line 44
            echo "\t\t\t<td data-cell=\"Client: \">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_first_name", [], "any", false, false, false, 44), "html", null, true);
            echo " ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_last_name", [], "any", false, false, false, 44), "html", null, true);
            echo " (";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_username", [], "any", false, false, false, 44), "html", null, true);
            echo ")</td>
\t\t\t<td data-cell=\"Location\">
\t\t\t\t";
            // line 46
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "location_name", [], "any", false, false, false, 46), "html", null, true);
            echo "
\t\t\t\t";
            // line 47
            if (((twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", true, true, false, 47) &&  !(null === twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 47))) &&  !twig_test_empty(twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 47)))) {
                // line 48
                echo "\t\t\t\t\t<br><br>
\t\t\t\t\tRM ";
                // line 49
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 49), "html", null, true);
                echo "
\t\t\t\t";
            }
            // line 51
            echo "\t\t\t</td>
\t\t\t<td data-cell=\"Request Category\">";
            // line 52
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "request_category", [], "any", false, false, false, 52), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Current Status\">";
            // line 53
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "status", [], "any", false, false, false, 53), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Priority\">
\t\t\t\t<span class=\"sort-value\">";
            // line 55
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "sort_value", [], "any", false, false, false, 55), "html", null, true);
            echo "</span>
\t\t\t\t";
            // line 56
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "priority", [], "any", false, false, false, 56), "html", null, true);
            echo "
\t\t\t</td>
\t\t\t<td data-cell=\"Created\">";
            // line 58
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "created", [], "any", false, false, false, 58), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Last Updated\">";
            // line 59
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "last_updated", [], "any", false, false, false, 59), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Due\">";
            // line 60
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "due_date", [], "any", false, false, false, 60), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Assigned\">";
            // line 61
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "assigned_tech", [], "any", false, false, false, 61), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Alert Levels\">";
            // line 62
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "alert_level", [], "any", false, false, false, 62), "html", null, true);
            echo "</td>
\t\t</tr>
\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['ticket'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 65
        echo "\t</tbody>
\t</table>

\t<h1>My Open Tickets</h1>
\t<table class=\"ticketsTable data-table\">
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
\t\t\t<th class=\"\">Assigned</th>
\t\t\t<th class=\"alertLevel\">Alert</th>
\t\t</tr>
\t</thead>
\t<tbody>
\t\t";
        // line 88
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["open_tickets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["ticket"]) {
            // line 89
            echo "\t\t<tr class=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "row_color", [], "any", false, false, false, 89), "html", null, true);
            echo "\">
\t\t\t<td data-cell=\"ID\"><a href=\"/controllers/tickets/edit_ticket.php?id=";
            // line 90
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 90), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 90), "html", null, true);
            echo "</a></td>
\t\t\t<td class=\"details\" data-cell=\"Request Detail\"><a href=\"/controllers/tickets/edit_ticket.php?id=";
            // line 91
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "id", [], "any", false, false, false, 91), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "title", [], "any", false, false, false, 91), "html", null, true);
            echo "</a>";
            echo twig_get_attribute($this->env, $this->source, $context["ticket"], "description", [], "any", false, false, false, 91);
            echo "</td>
\t\t\t<td class=\"latestNote\" data-cell=\"Latest Note:\"><strong>";
            // line 92
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note_author", [], "any", false, false, false, 92), "html", null, true);
            echo ": </strong> ";
            echo twig_get_attribute($this->env, $this->source, $context["ticket"], "latest_note", [], "any", false, false, false, 92);
            echo "</td>
\t\t\t<td data-cell=\"Client: \">";
            // line 93
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_first_name", [], "any", false, false, false, 93), "html", null, true);
            echo " ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_last_name", [], "any", false, false, false, 93), "html", null, true);
            echo " (";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "client_username", [], "any", false, false, false, 93), "html", null, true);
            echo ")</td>
\t\t\t<td data-cell=\"Location\">
\t\t\t\t";
            // line 95
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "location_name", [], "any", false, false, false, 95), "html", null, true);
            echo "
\t\t\t\t";
            // line 96
            if ( !(null === twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 96))) {
                // line 97
                echo "\t\t\t\t\t<br><br>
\t\t\t\t\tRM ";
                // line 98
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "room", [], "any", false, false, false, 98), "html", null, true);
                echo "
\t\t\t\t";
            }
            // line 100
            echo "\t\t\t</td>
\t\t\t<td data-cell=\"Request Category\">";
            // line 101
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "request_category", [], "any", false, false, false, 101), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Current Status\">";
            // line 102
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "status", [], "any", false, false, false, 102), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Priority\">
\t\t\t\t<span class=\"sort-value\">";
            // line 104
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "sort_value", [], "any", false, false, false, 104), "html", null, true);
            echo "</span>
\t\t\t\t";
            // line 105
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "priority", [], "any", false, false, false, 105), "html", null, true);
            echo "
\t\t\t</td>
\t\t\t<td data-cell=\"Created\">";
            // line 107
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "created", [], "any", false, false, false, 107), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Last Updated\">";
            // line 108
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "last_updated", [], "any", false, false, false, 108), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Due\">";
            // line 109
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "due_date", [], "any", false, false, false, 109), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Assigned\">";
            // line 110
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "assigned_tech", [], "any", false, false, false, 110), "html", null, true);
            echo "</td>
\t\t\t<td data-cell=\"Alert Levels\">";
            // line 111
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["ticket"], "alert_level", [], "any", false, false, false, 111), "html", null, true);
            echo "</td>
\t\t</tr>
\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['ticket'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 114
        echo "\t</tbody>
</table>
";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "tickets.twig";
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
        return array (  339 => 114,  330 => 111,  326 => 110,  322 => 109,  318 => 108,  314 => 107,  309 => 105,  305 => 104,  300 => 102,  296 => 101,  293 => 100,  288 => 98,  285 => 97,  283 => 96,  279 => 95,  270 => 93,  264 => 92,  256 => 91,  250 => 90,  245 => 89,  241 => 88,  216 => 65,  207 => 62,  203 => 61,  199 => 60,  195 => 59,  191 => 58,  186 => 56,  182 => 55,  177 => 53,  173 => 52,  170 => 51,  165 => 49,  162 => 48,  160 => 47,  156 => 46,  146 => 44,  142 => 42,  134 => 40,  132 => 39,  124 => 38,  118 => 37,  113 => 36,  109 => 35,  87 => 15,  83 => 13,  75 => 11,  68 => 9,  64 => 8,  59 => 7,  55 => 6,  52 => 5,  50 => 4,  46 => 3,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "tickets.twig", "/var/www/html/public/views/tickets.twig");
    }
}
