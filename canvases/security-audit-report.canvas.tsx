import {
  Card,
  CardBody,
  CardHeader,
  Grid,
  H1,
  H2,
  Pill,
  Row,
  Stack,
  Table,
  Text,
  useHostTheme,
} from "cursor/canvas";

const featureRows = [
  { area: "Authentication", status: "Implemented", notes: "Login/logout, registration, role sessions." },
  { area: "RBAC", status: "Implemented", notes: "Role gates via requireRole() for admin/teacher/student pages." },
  { area: "Course Management", status: "Implemented", notes: "Teacher create/edit courses, student enrollment flow." },
  { area: "Payments", status: "Implemented", notes: "Mock payment records linked to enrollments." },
  { area: "Announcements", status: "Implemented", notes: "Admin creates announcements with XSS toggle behavior." },
  { area: "Audit Logs", status: "Implemented", notes: "Action logging via logAudit(), admin viewing UI." },
  { area: "Security Settings", status: "Partially implemented", notes: "Toggle page exists; DB and .env sync is fragile/inconsistent." },
  { area: "File Upload", status: "Implemented", notes: "Teacher material upload; vulnerable/secure paths in uploadFile()." },
];

const vulnRows = [
  { vuln: "SQL Injection", severity: "High", evidence: "Raw SQL in auth/search when SQLi protection is OFF." },
  { vuln: "Stored XSS", severity: "High", evidence: "Announcement and about_me rendered raw when XSS protection is OFF." },
  { vuln: "IDOR", severity: "High", evidence: "Course edit ownership check bypass when IDOR protection is OFF." },
  { vuln: "CSRF", severity: "High", evidence: "POST handlers enforce CSRF regardless of CSRF toggle (toggle ineffective)." },
  { vuln: "Missing route flaws", severity: "Medium", evidence: "Links to missing files: course.php, forgot-password.php, edit-user.php, etc." },
  { vuln: "GET destructive actions", severity: "Medium", evidence: "User/course/announcement deletes triggered by GET query params." },
  { vuln: "Session hardening gaps", severity: "Medium", evidence: "No session_regenerate_id() after login; cookie flags not explicitly hardened." },
  { vuln: "Error disclosure", severity: "Medium", evidence: "DB failures and exception messages exposed to users in multiple flows." },
  { vuln: "Upload path mismatch", severity: "Medium", evidence: "UPLOAD_DIR env points to /var/www/html/uploads while code expects /app/uploads paths." },
];

const controlRows = [
  { control: "Prepared statements", status: "Present", notes: "Used widely through dbSelect/dbInsert wrappers." },
  { control: "Output encoding", status: "Present", notes: "htmlspecialchars in many views when protection enabled." },
  { control: "CSRF token helpers", status: "Present", notes: "generateCSRFToken()/verifyCSRFToken() implemented and used." },
  { control: "Role checks", status: "Present", notes: "requireRole() used at entry points." },
  { control: "Password hashing", status: "Present", notes: "password_hash/password_verify in auth helpers." },
  { control: "Upload validation", status: "Present", notes: "Extension + MIME checks exist in secure upload path." },
];

const requirementRows = [
  { requirement: "Complete project structure scan", status: "Fully satisfied", notes: "All files inventoried." },
  { requirement: "Analyze all PHP files", status: "Fully satisfied", notes: "33 PHP files reviewed." },
  { requirement: "Analyze Docker configuration", status: "Fully satisfied", notes: "docker-compose + Dockerfile reviewed." },
  { requirement: "Analyze DB schema", status: "Fully satisfied", notes: "schema.sql + init.sql reviewed." },
  { requirement: "Authentication flow analysis", status: "Fully satisfied", notes: "login/register/logout/session timeout traced." },
  { requirement: "Authorization analysis", status: "Fully satisfied", notes: "RBAC + ownership checks reviewed." },
  { requirement: "API endpoint analysis", status: "Missing", notes: "No dedicated API endpoints exist." },
  { requirement: "Admin functionality analysis", status: "Fully satisfied", notes: "Dashboard/users/courses/payments/announcements/security settings reviewed." },
  { requirement: "File upload analysis", status: "Fully satisfied", notes: "Teacher upload flow + storage paths reviewed." },
  { requirement: "Session management analysis", status: "Fully satisfied", notes: "Session lifecycle and timeout analyzed." },
  { requirement: "Password storage analysis", status: "Fully satisfied", notes: "bcrypt currently used; weak-hash mode missing." },
  { requirement: "Container networking analysis", status: "Fully satisfied", notes: "Single bridge network with web/mysql/phpmyadmin." },
  { requirement: "Environment variable analysis", status: "Fully satisfied", notes: ".env parser and toggles reviewed." },
  { requirement: "Docker volume analysis", status: "Fully satisfied", notes: "Bind mounts + mysql volume reviewed." },
  { requirement: "Exposed port analysis", status: "Fully satisfied", notes: "8080, 8081, 3307 exposed to host." },
  { requirement: "Required 7 vulnerability toggles", status: "Partially satisfied", notes: "Current toggles differ; SSH/Backup/HTTP/WeakHash toggles absent." },
];

export default function SecurityAuditReport() {
  const theme = useHostTheme();

  return (
    <Stack gap={16} style={{ padding: 20, background: theme.canvas.background }}>
      <H1>MyEduConnect Security Audit (Pre-Modification)</H1>
      <Text>
        Scope: PHP application, SQL schema, Docker stack, role flows, vulnerability controls, and assignment readiness.
      </Text>

      <Card>
        <CardHeader title="Current Status Snapshot" />
        <CardBody>
          <Row gap={8}>
            <Pill tone="danger">Not merge-ready for assignment target</Pill>
            <Pill tone="warning">Toggle model inconsistent</Pill>
            <Pill tone="warning">Broken routes present</Pill>
            <Pill tone="info">Core LMS mostly functional</Pill>
          </Row>
        </CardBody>
      </Card>

      <Grid columns={2} gap={16}>
        <Card>
          <CardHeader title="Existing Features" />
          <CardBody>
            <Table
              columns={[
                { key: "area", title: "Feature Area" },
                { key: "status", title: "Status" },
                { key: "notes", title: "Notes" },
              ]}
              data={featureRows}
            />
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Existing Security Controls" />
          <CardBody>
            <Table
              columns={[
                { key: "control", title: "Control" },
                { key: "status", title: "Status" },
                { key: "notes", title: "Notes" },
              ]}
              data={controlRows}
            />
          </CardBody>
        </Card>
      </Grid>

      <Card>
        <CardHeader title="Existing Vulnerabilities (Observed in Code)" />
        <CardBody>
          <Table
            columns={[
              { key: "vuln", title: "Vulnerability" },
              { key: "severity", title: "Severity" },
              { key: "evidence", title: "Evidence" },
            ]}
            data={vulnRows}
          />
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Assignment Requirement Coverage" />
        <CardBody>
          <Table
            columns={[
              { key: "requirement", title: "Requirement" },
              { key: "status", title: "Status" },
              { key: "notes", title: "Notes" },
            ]}
            data={requirementRows}
          />
        </CardBody>
      </Card>

      <Card>
        <CardHeader title="Critical Gaps Blocking Requested End-State" />
        <CardBody>
          <Stack gap={6}>
            <Text>1) Toggle model currently uses protection flags, not the requested vulnerability-manager schema.</Text>
            <Text>2) Required vulnerabilities not implemented yet: Weak SSH Credentials, Backup Exposure, Weak Hashing toggle mode, HTTP/HTTPS switch.</Text>
            <Text>3) Multiple dead/broken links/routes point to files that do not exist.</Text>
            <Text>4) No dedicated API endpoints despite assignment expectation to analyze/demo API transport mode.</Text>
            <Text>5) CSRF toggle exists but does not genuinely switch secure vs vulnerable behavior across handlers.</Text>
          </Stack>
        </CardBody>
      </Card>

      <H2>Recommended Next Execution Phase</H2>
      <Text>
        Proceed with refactor in this order: normalize vulnerability manager schema, remove dead routes/code, implement the 7 required toggles with real vulnerable/secure branches, add evidence folders/docs, then run final exploit/re-test audit.
      </Text>
    </Stack>
  );
}
