---
title: Starting from a login page
period: January – April 2024
summary: The first three months. A Symfony skeleton with sign-up and login became a catalogue with a pricing engine, suppliers, image uploads, a three-tier test suite and a deploy script, and the skeleton called Butterfly became an e-commerce back end called Turtle.
refs:
  - label: Overview
    url: https://github.com/myvars/supplymars/blob/main/Docs/01-overview.md
  - label: Testing guide
    url: https://github.com/myvars/supplymars/blob/main/Docs/07-testing.md
---

The project started on 25 January 2024 as Butterfly, a Symfony skeleton with sign-up, login, Tailwind and Turbo; [why I built it](/log/why-i-built-supplymars) is a post of its own. Everything after was built on that login page, and registration has been part of it since that day.

I wrote it with ChatGPT in a window beside the editor, adapting what it gave me and pasting it in; [that story](/log/from-chatgpt-to-claude-code) has its own post.

## February: a catalogue

On 2 February the catalogue arrived in an afternoon: VAT rates, categories, subcategories, manufacturers and products, with admin screens for VAT rates, manufacturers and categories by the evening. A week later the screens shared [one CRUD helper](/log/evolving-a-crud-abstraction), the first of four versions.

The rest of February built the two pieces that make the catalogue more than a list. On 23 February [prices](/log/pretty-prices) started being worked out from cost, a cascading markup, VAT and a rounding rule. On 29 February suppliers arrived, each product with [an active source](/log/choosing-a-supplier) chosen from its suppliers' stock and cost.

## March: images and tests

Product images went to S3 on 7 March, through Flysystem, with LiipImagine making thumbnails and VichUploaderBundle handling the upload. Five days later Vich was gone, replaced by a small `UploadHelper` that writes the file to Flysystem with a unique name and deletes the one it replaces. The next day images could be put in order by dragging them, with SortableJS and a position column.

On 23 March came the test suite: 48 test files and 304 test methods, in three tiers. Twenty-two unit test files ran without the kernel, sixteen integration test files used the database through Foundry factories, and ten application test files drove the controllers. The same commit fixed a pricing bug a month old: Pretty 95 had been rounding prices the wrong way, and the new tests pin the right answers.

The end of the month brought Symfony UX Icons and the index pages swapped their data tables for lists.

## April: a server and a name

On 3 April the project got deploy scripts for a Bitnami server: Apache virtual hosts, a setup and a startup script, and a supervisor config for the Messenger worker. The same commit renamed the project. The README now said Turtle, "a simple e-commerce back-end built with modern PHP/Symfony". The Bitnami box lasted until June 2025, and [leaving it](/log/leaving-the-bitnami-box) is a story of its own.

On 10 April the product form got dependent fields, through the SymfonyCasts dynamic forms package: pick a category, and the subcategory list holds only that category's subcategories. Then came mobile styles, a controller clean-up and the second version of the CRUD layer. On the last day of the month, Turtle took its first [orders](/log/orders-purchase-orders-and-a-status-tree).

*Update, November 2026: the suite now mirrors the bounded contexts, and has about 1,300 test methods. The project became SupplyMars with the [Mars theme](/log/search-a-mars-theme-and-going-public) in October 2024, and runs on PHP 8.5.*
