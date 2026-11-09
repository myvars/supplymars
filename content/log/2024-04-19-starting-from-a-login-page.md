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

I wrote it by hand, as I did nearly all of the first two years, with ChatGPT for the occasional question; [how that changed](/log/from-hand-written-code-to-claude-code) has its own post.

## February: a catalogue

Early in February the catalogue arrived in an afternoon: VAT rates, categories, subcategories, manufacturers and products, and admin screens for VAT rates, manufacturers and categories came with it. A week later the screens shared [one CRUD helper](/log/evolving-a-crud-abstraction), the first of four versions.

The rest of February built the two pieces that make the catalogue more than a list. Late in the month [prices](/log/pretty-prices) started being worked out from cost, a cascading markup, VAT and a rounding rule, and within a week suppliers arrived, each product with [an active source](/log/choosing-a-supplier) chosen from its suppliers' stock and cost.

## March: images and tests

In early March product images went to S3, through Flysystem, with LiipImagine making thumbnails and VichUploaderBundle handling the upload. Within a week Vich was gone, replaced by a small `UploadHelper` that writes the file to Flysystem with a unique name and deletes the one it replaces. Soon after, images could be put in order by dragging them, with SortableJS and a position column.

In late March came the test suite: 48 test files and 304 test methods, in three tiers. Twenty-two unit test files ran without the kernel, sixteen integration test files used the database through Foundry factories, and ten application test files drove the controllers. The same commit fixed [Pretty 95](/log/pretty-prices), which had been rounding prices the wrong way for a month.

The end of the month brought Symfony UX Icons, and the index pages swapped their data tables for lists.

## April: a server and a name

Early in April the project got deploy scripts for a Bitnami server: Apache virtual hosts, a setup and a startup script, and a Supervisor config for the Messenger worker. The same commit renamed the project. The README now said Turtle, "a simple e-commerce back-end built with modern PHP/Symfony". The Bitnami box lasted until June 2025, and [leaving it](/log/leaving-the-bitnami-box) is a story of its own.

A week later the product form got dependent fields, through the SymfonyCasts dynamic forms package: pick a category, and the subcategory list holds only that category's subcategories. Then came mobile styles, a controller clean-up and the second version of the CRUD layer. At the end of the month, Turtle took its first [orders](/log/orders-purchase-orders-and-a-status-tree).

*Update, November 2026: the suite now mirrors the bounded contexts, and has about 1,300 test methods. The project became SupplyMars with the [Mars theme](/log/search-a-mars-theme-and-going-public) in October 2024, and runs on PHP 8.5.*
