---
name: playwright-tasks
description: Automate browser tasks using Playwright MCP - login, navigate, take screenshots. Use ONLY when user explicitly asks to open a browser, take a screenshot of a website, or automate browser interactions.
disable-model-invocation: true
argument-hint: "[url or description of browser task]"
---

# Playwright Browser Automation

Use the Playwright MCP server tools to control a real browser. This skill is for tasks like logging into websites, navigating pages, filling forms, clicking elements, and taking screenshots.

## Available Playwright MCP Tools

Use these MCP tools (provided by the `playwright` MCP server):

- **browser_navigate** — Go to a URL
- **browser_click** — Click an element (by text, selector, or coordinates)
- **browser_fill** — Type into an input field
- **browser_screenshot** — Capture the current page
- **browser_snapshot** — Get the accessibility tree (structured page content)
- **browser_wait_for_text** — Wait for specific text to appear
- **browser_hover** — Hover over an element
- **browser_select_option** — Select from dropdowns
- **browser_press_key** — Press keyboard keys (Enter, Tab, etc.)
- **browser_close** — Close the browser when done

## Workflow

1. **Navigate** to the target URL
2. **Snapshot** the page to understand its structure (accessibility tree)
3. **Interact** — fill forms, click buttons, wait for content
4. **Screenshot** to capture the result
5. **Save** screenshot to the project directory if requested
6. **Close** the browser when done

## Guidelines

- Always take a **snapshot** before interacting to find correct element references
- After login or navigation, **wait** for the page to settle before screenshotting
- Use **accessibility tree refs** (from snapshot) for clicking/filling — more reliable than CSS selectors
- If a page loads dynamically, use `browser_wait_for_text` before proceeding
- Save screenshots to the **project root** unless the user specifies otherwise
- **Never hardcode credentials** in skill files — only use credentials provided by the user in the conversation

## Arguments

`$ARGUMENTS` — The user's description of what browser task to perform (URL, login details, what to capture, etc.)
