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

/* ticket_base.twig */
class __TwigTemplate_4e78a8eabe5273cf16b2fb61b1b0788c extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'menu' => [$this, 'block_menu'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "base.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("base.twig", "ticket_base.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_menu($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        echo "<ul id=\"subMenu\">
    <li><a href=\"/controllers/tickets/create_ticket.php\">Create Ticket</a></li>
    ";
        // line 6
        if ((twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_supervisor", [], "any", false, false, false, 6) && (($context["subord_count"] ?? null) > 0))) {
            // line 7
            echo "    <li><a href=\"/controllers/tickets/subordinate_tickets.php\">Subordinate Tickets</a></li>
    ";
        }
        // line 9
        echo "    ";
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_location_manager", [], "any", false, false, false, 9)) {
            // line 10
            echo "    <li><a href=\"/controllers/tickets/location_tickets.php\">Location Tickets</a></li>
    ";
        }
        // line 12
        echo "    <li><a href=\"/tickets.php\">My Tickets (";
        echo twig_escape_filter($this->env, ($context["num_assigned_tickets"] ?? null), "html", null, true);
        echo ")</a></li>
    ";
        // line 13
        if (twig_get_attribute($this->env, $this->source, ($context["user_permissions"] ?? null), "is_tech", [], "any", false, false, false, 13)) {
            // line 14
            echo "        ";
            if ((($context["num_flagged_tickets"] ?? null) > 0)) {
                // line 15
                echo "        <li><a href=\"/controllers/tickets/flagged_tickets.php\">Flagged Tickets (";
                echo twig_escape_filter($this->env, ($context["num_flagged_tickets"] ?? null), "html", null, true);
                echo ")</a></li>
        ";
            }
            // line 17
            echo "        <li><a href=\"/controllers/tickets/recent_tickets.php\">Recent Tickets</a></li>
        <li><a href=\"/controllers/tickets/search_tickets.php\">Search Tickets</a></li>
    ";
        } else {
            // line 20
            echo "    <li><a href=\"/tickets.php\">My Tickets</a></li>
    <li><a href=\"/controllers/tickets/ticket_history.php\">Ticket History</a></li>
    ";
        }
        // line 23
        echo "</ul>
";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "ticket_base.twig";
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
        return array (  93 => 23,  88 => 20,  83 => 17,  77 => 15,  74 => 14,  72 => 13,  67 => 12,  63 => 10,  60 => 9,  56 => 7,  54 => 6,  50 => 4,  46 => 3,  35 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "ticket_base.twig", "/var/www/html/public/views/ticket_base.twig");
    }
}
