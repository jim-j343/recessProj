# ACES — Unified Platform UI

Refined, unified user interface for the **ACES** desktop application, derived from the
Stitch screen designs and the *Precision Logic* design system (`DESIGN.md`). This is the
**design/UI phase only** — no controller logic or backend wiring yet.

All screens were rebranded to a single **ACES** identity (the source mockups mixed
"EduNexus", "EduForum" and "Academic Portal") and re-styled onto one consistent
black-primary, hairline-bordered visual language.

## What's here

Two synchronized deliverables share one design system:

| # | Screen | HTML mockup | JavaFX FXML |
|---|--------|-------------|-------------|
| 01 | Login (role-aware) | `screens/01-login.html` | `fxml/Login.fxml` |
| 02 | Register / Onboarding | `screens/02-register.html` | `fxml/Register.fxml` |
| 03 | Forum Dashboard | `screens/03-forum-dashboard.html` | `fxml/ForumDashboard.fxml` |
| 04 | Admin Analytics Overview | `screens/04-admin-analytics.html` | `fxml/AdminAnalytics.fxml` |
| 05 | Quiz Management / Config | `screens/05-quiz-management.html` | `fxml/QuizManagement.fxml` |
| 06 | Quiz Focus (proctored) Mode | `screens/06-quiz-focus-mode.html` | `fxml/QuizFocusMode.fxml` |
| 07 | Participation Grading | `screens/07-participation-grading.html` | `fxml/ParticipationGrading.fxml` |
| 08 | Compliance Monitoring | `screens/08-compliance-monitoring.html` | `fxml/ComplianceMonitoring.fxml` |
| 09 | Student Assessment Overview | `screens/09-student-assessment.html` | `fxml/StudentAssessment.fxml` |
| 10 | Topic Creation (lecturer) | `screens/10-topic-creation.html` | `fxml/TopicCreation.fxml` |
| 11 | Topic Detail / Discussion | `screens/11-topic-detail.html` | `fxml/TopicDetail.fxml` |

- **HTML mockups** — the browsable visual spec. Open `ui-design/index.html` for the gallery.
  Shared tokens live in `ui-design/css/aces-design-system.css`.
- **JavaFX FXML** — `src/main/resources/forum/fxml/*.fxml`, all wired to the shared
  `src/main/resources/forum/css/aces.css`. UI only — `fx:id`s are in place for controllers later.

## Preview the desktop UI

`MainApp.java` is a UI preview launcher. Change the `SCREEN` constant to any FXML name
(`Login`, `ForumDashboard`, `QuizManagement`, …) and run:

```
./mvnw clean javafx:run
```

## Design system — Precision Logic (ACES)

*Minimalist Corporate Tech / "Quiet Premium"* — clarity through whitespace, alignment and
typographic rhythm; hairline borders and tonal layers instead of heavy shadows.

**Colors**
- Ground `#F8FAFC` · Surface `#FFFFFF` · Recessed `#F1F5F9`
- Ink (primary text / primary buttons) `#0F172A` · Secondary `#334155` · Muted `#64748B`
- Border `#E2E8F0`
- Accent (interactive / focus only) `#2563EB`
- State: success `#16A34A` · warning `#B45309` · danger `#DC2626`

**Type** — Inter throughout. Display 36 / Headline 28 / Title 20 / Body 14 / Label 12.

**Shape** — 4px inputs & buttons, 8px cards & modals.

**Components** — primary (solid ink) / secondary (bordered) / accent buttons; label-above
inputs with blue focus halo; hairline-bordered cards (no default shadow); pill chips
(`#F1F5F9` / `#475569`); segmented controls; low-contrast status badges.

**Spacing** — 8px base unit; 24px gutters; content centered up to 1280px.

## Notes
- HTML uses inline Lucide-style SVG icons; the FXML uses text/emoji glyph placeholders that
  can be swapped for an icon font (e.g. Ikonli) during implementation.
- Charts: HTML uses lightweight CSS bars; FXML uses native `BarChart`.
- Next phase: attach controllers, models and the offline-sync service to these screens.
