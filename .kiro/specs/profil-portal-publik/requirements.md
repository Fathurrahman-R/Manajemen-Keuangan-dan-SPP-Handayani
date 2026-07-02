# Requirements Document

## Introduction

This document specifies the requirements for a public profile/landing page (Profil Portal Publik) for Yayasan Handayani. The page will be served as the first page visitors see before the login page in frontend-v2. It replicates the exact visual design from the Lovable.ai reference implementation (portal-reference/handayani-joyful-portal), converting React/TanStack components to Blade + Alpine.js + Tailwind CSS v4.

The page is PUBLIC (not behind authentication) and uses standard Laravel routing, controllers, and Blade views — NOT Filament components or layout.

## Glossary

- **Public Page**: The landing/profil page accessible without authentication, served before login
- **Reference Implementation**: The Lovable.ai TypeScript/React/TanStack implementation in portal-reference/handayani-joyful-portal
- **Blade Components**: Reusable view components in resources/views/components/public/
- **Alpine.js**: Lightweight JavaScript framework replacing React useState/useQuery for interactivity
- **Tailwind v4**: CSS framework used via Vite, with separate entry point from Filament theme
- **SITE Config**: Centralized site configuration (name, tagline, address, contact info, portal URL)

## Requirements

### Requirement 1: Public Route and Controller

**User Story:** As a visitor, I want to access the public profile page at the root URL, so that I can learn about Yayasan Handayani before logging in.

#### Acceptance Criteria

1. THE System SHALL serve the public profile page at the root route "/"
2. THE System SHALL use a standard Laravel controller (PublicPageController) not a Filament Page
3. THE System SHALL register the route in routes/web.php (not as Filament resource/page)
4. THE System SHALL NOT require authentication or Sanctum middleware for this route
5. THE System SHALL return a Blade view from resources/views/public/index.blade.php

### Requirement 2: Site Configuration

**User Story:** As a developer, I want centralized site configuration, so that institutional details can be updated in one place.

#### Acceptance Criteria

1. THE System SHALL provide a config file config/handayani-public.php with site settings
2. THE config file SHALL contain: name, shortName, tagline, address, phone, email, whatsappNumber, sppPortalUrl
3. THE System SHALL use config values in Blade views via config('handayani-public.key')
4. THE config values SHALL match the reference implementation site.ts exactly

### Requirement 3: Navigation Header (Nav)

**User Story:** As a visitor, I want a sticky navigation header with smooth scroll links, so that I can navigate sections easily.

#### Acceptance Criteria

1. WHEN the page loads, THE Nav Component SHALL render a sticky header at top-0 with z-50
2. THE Nav Component SHALL display the site logo (SVG) and shortName
3. THE Nav Component SHALL show navigation links: Beranda, Tentang, Jenjang, SPP, Kontak
4. THE Nav Component SHALL show a "Portal SPP" button linking to config('handayani-public.sppPortalUrl')
5. WHEN viewport is mobile (< md), THE Nav Component SHALL show a hamburger menu button
6. WHEN hamburger is clicked, THE Nav Component SHALL toggle a mobile menu dropdown
7. THE Nav Component SHALL use Alpine.js x-data for open/close state (replacing React useState)
8. THE Nav Component SHALL have smooth scroll behavior for anchor links

### Requirement 4: Hero Section

**User Story:** As a visitor, I want an engaging hero section with illustration, so that I understand the institution's identity immediately.

#### Acceptance Criteria

1. THE Hero Component SHALL render a section with id="beranda"
2. THE Hero Component SHALL display a geometric pattern background (SVG mashrabiya pattern)
3. THE Hero Component SHALL show a badge "Yayasan Pendidikan Islam" with accent dot
4. THE Hero Component SHALL display the site name as H1 (font-display, text-4xl to text-6xl)
5. THE Hero Component SHALL display the tagline and description text
6. THE Hero Component SHALL show two CTAs: "Kenali Kami" (scroll to #tentang) and "Portal Pembayaran SPP" (link to sppPortalUrl)
7. THE Hero Component SHALL display three statistics: "3 Jenjang Terpadu", "20+ Tahun Berdiri", "100% Kurikulum Nasional"
8. THE Hero Component SHALL show the hero illustration image on the right (lg:col-span-5)
9. THE Hero Component SHALL use the exact Tailwind classes from reference for spacing, typography, colors

### Requirement 5: About Section (Tentang)

**User Story:** As a visitor, I want to read about the institution's mission, vision, and values, so that I understand its educational philosophy.

#### Acceptance Criteria

1. THE About Component SHALL render a section with id="tentang" and border-y border-border bg-surface
2. THE About Component SHALL display "Tentang Kami" label and main heading
3. THE About Component SHALL show two cards: Misi (primary accent) and Visi (accent color)
4. THE About Component SHALL display "Nilai Institusional" with three values in a bordered list
5. THE three values SHALL be: Integritas (01), Profesionalisme (02), Keteladanan (03)
6. Each value SHALL show number, title, and description in grid layout
7. THE About Component SHALL use Reveal animation (opacity/translateY) via Alpine.js IntersectionObserver

### Requirement 6: Education Levels Section (Jenjang)

**User Story:** As a parent, I want to see the three education levels offered, so that I can choose the right one for my child.

#### Acceptance Criteria

1. THE Jenjang Component SHALL render a section with id="jenjang" and bg-background
2. THE Jenjang Component SHALL display section label and heading
3. THE Jenjang Component SHALL show three cards in grid (1 col mobile, 2 col md, 3 col lg)
4. THE three levels SHALL be: KB/PAUD (Usia 2-4), TK (Usia 4-6), MI (Usia 6-12)
5. Each card SHALL show: code badge, age, name, description, program list with check icons
6. Each card SHALL have hover effect: -translate-y-1, border-primary/40, shadow-xl
7. Each card SHALL have "Info pendaftaran" link scrolling to #kontak
8. THE Jenjang Component SHALL use Reveal animation with staggered delay

### Requirement 7: SPP Portal CTA Section

**User Story:** As a parent, I want a prominent call-to-action for the SPP payment portal, so that I can easily access payment services.

#### Acceptance Criteria

1. THE SppCta Component SHALL render a section with id="spp" with gradient background (primary to accent)
2. THE SppCta Component SHALL show geometric pattern overlay with higher opacity
3. THE SppCta Component SHALL display "Layanan Orang Tua" label and "Portal Pembayaran SPP" heading
4. THE SppCta Component SHALL show two buttons: "Masuk Portal SPP" (primary) and "Butuh bantuan?" (outline to #kontak)
5. THE SppCta Component SHALL display three badges: Terverifikasi, Real-time, Aman SSL
6. Each badge SHALL have icon, label, and description in frosted glass cards
7. THE SppCta Component SHALL use Reveal animation

### Requirement 8: Contact Section (Kontak)

**User Story:** As a visitor, I want to see contact information and location, so that I can reach the institution.

#### Acceptance Criteria

1. THE Kontak Component SHALL render a section with id="kontak" with bg-surface border-t
2. THE Kontak Component SHALL show contact info on left (lg:col-span-5): label, heading, description
3. THE Kontak Component SHALL display three contact items: Alamat (MapPin), Telepon (Phone), Email (Mail)
4. Each contact item SHALL have icon in colored circle, label, and value (phone/email as links)
5. THE Kontak Component SHALL show WhatsApp button with MessageCircle icon linking to wa.me
6. THE Kontak Component SHALL show embedded OpenStreetMap iframe on right (lg:col-span-7)
7. THE iframe SHALL have title "Peta Lokasi Handayani" and lazy loading

### Requirement 9: Footer

**User Story:** As a visitor, I want a footer with navigation and copyright, so that I have access to key links and legal info.

#### Acceptance Criteria

1. THE Footer Component SHALL render with bg-background and top gradient border
2. THE Footer Component SHALL show logo + shortName on left
3. THE Footer Component SHALL show tagline text
4. THE Footer Component SHALL show navigation links: Tentang, Jenjang, Portal SPP, Kontak
5. THE Footer Component SHALL show address and copyright with current year
6. THE Footer Component SHALL show "Dibangun dengan amanah." tagline

### Requirement 10: Geometric Pattern Component

**User Story:** As a developer, I want a reusable geometric pattern component, so that the signature mashrabiya design appears consistently.

#### Acceptance Criteria

1. THE System SHALL provide a Blade component public.geometric-pattern
2. THE component SHALL accept opacity, stroke, and class props
3. THE component SHALL render the exact SVG mashrabiya pattern from reference (8-point star tessellation)
4. THE pattern SHALL use width="100%" height="100%" with patternUnits="userSpaceOnUse"
5. THE pattern SHALL have three paths: 8-point star, outer octagon, inner rotated square

### Requirement 11: Reveal Animation (IntersectionObserver)

**User Story:** As a visitor, I want smooth scroll animations, so that content reveals elegantly as I scroll.

#### Acceptance Criteria

1. THE System SHALL provide a Blade component public.reveal
2. THE component SHALL accept delay, class, and as (tag) props
3. THE component SHALL use Alpine.js with IntersectionObserver to add "reveal-in" class when in view
4. THE CSS SHALL define .reveal { opacity: 0; transform: translateY(16px); transition: 700ms ease }
5. THE CSS SHALL define .reveal-in { opacity: 1; transform: translateY(0) }
6. THE component SHALL apply transition-delay from delay prop

### Requirement 12: Public Asset Entry Point

**User Story:** As a developer, I want a separate Vite entry for public pages, so that public page styles don't mix with Filament admin theme.

#### Acceptance Criteria

1. THE System SHALL create resources/css/public.css as entry point
2. THE System SHALL create resources/js/public.js as entry point
3. THE public.css SHALL import Tailwind v4 and define public page theme colors (matching reference styles.css)
4. THE public.css SHALL define CSS variables for: background, foreground, primary, accent, surface, border, muted, font-display (Manrope), font-sans (Inter)
6. THE public.js SHALL import public.css and Alpine.js
7. THE vite.config.js SHALL include public.css and public.js in input array
8. THE public Blade layout SHALL use @vite(['resources/css/public.css', 'resources/js/public.js'])

### Requirement 13: Blade Layout and Component Structure

**User Story:** As a developer, I want organized Blade components, so that the page is maintainable and follows Laravel conventions.

#### Acceptance Criteria

1. THE System SHALL create resources/views/layouts/public.blade.php as base layout
2. THE layout SHALL include DOCTYPE, html, head with meta tags, @vite directive, body
3. THE layout SHALL yield 'content' section
4. THE System SHALL create components in resources/views/components/public/:
   - nav.blade.php
   - hero.blade.php
   - about.blade.php
   - jenjang.blade.php
   - spp-cta.blade.php
   - kontak.blade.php
   - footer.blade.php
   - geometric-pattern.blade.php
   - reveal.blade.php
5. THE index.blade.php SHALL extend public layout and include all section components in order
6. ALL Tailwind classes SHALL be written directly in templates (no @apply)

### Requirement 14: Alpine.js Integration

**User Story:** As a developer, I want Alpine.js for client-side interactivity, so that I don't need Livewire for this static public page.

#### Acceptance Criteria

1. THE System SHALL include Alpine.js via CDN or npm in public.js
2. THE Nav Component SHALL use x-data="{ open: false }" for mobile menu toggle
3. THE Reveal Component SHALL use x-data with IntersectionObserver for scroll animations
4. NO Livewire components SHALL be used in public page
5. NO Filament components SHALL be used in public page

### Requirement 15: Hero Illustration Image

**User Story:** As a visitor, I want to see the school illustration in the hero, so that the page feels welcoming.

#### Acceptance Criteria

1. THE System SHALL copy hero-illustration.jpg to frontend-v2/public/images/hero-illustration.jpg
2. THE Hero Component SHALL reference the image via asset('images/hero-illustration.jpg')
3. THE image SHALL have alt text "Ilustrasi gedung sekolah Handayani"
4. THE image SHALL be displayed in rounded-3xl container with gradient glow backdrop