# GitHub Copilot Instructions - Client KPU System
- Repository ini digunakan oleh role "client" untuk mengelola data lembur dan piket karyawan, proyek, dan SPK yang terkait dengan client tersebut. Role "client" memiliki akses terbatas hanya untuk melihat dan mengelola data yang terkait dengan client mereka sendiri.

## Verification Protocol

### Before executing a task, AI MUST:
1. Repeat understanding of the task in 1-2 sentences
2. Ask for clarification if there is ambiguity
3. Confirm scope of work before starting coding
4. AI does not need to wait for confirmation if the task is very clear and unambiguous

### When AI must ask questions:
- Instructions contain ambiguous words ("fix", "update", "make better")
- Task can be interpreted in more than one way
- Unclear which file/function is meant
- Changes could break other features (breaking change)

### Verification format used:
> "Before I start, I understand this task as:
> [summary of AI understanding]
> Is this correct? Anything that needs clarification?"

---

## 📋 Template Verification Prompt

### Pola 1 — **Repeat Back**
```
Before starting, repeat your understanding of this task,
then ask if anything is unclear.
```

### Pola 2 — **Assumption Listing**
```
Before starting, list all assumptions you are making.
Mark which ones need user confirmation.
```

### Pola 3 — **Clarifying Questions First**
```
Dont start coding yet. First ask up to 3 most important clarifying questions.
```

### Pola 4 — **Confidence Check**
```
Rate your confidence in understanding this task (1-10).
If below 8, ask what is still unclear.
```

## About This Project
This is a Laravel-based Human Resource Management system designed to manage employees, job assignments, attendance tracking, payroll processing, and overtime management. The system handles comprehensive HR operations including employee placement on projects, salary calculations, cost structure management, and collective allowances/deductions.

## Core Technologies
- **PHP**: 8.3.3
- **Laravel**: v12
- **Database**: MySQL
- **Docker**: PHP 8.x container
- **Frontend**: Blade

### How to run and test php code in this project:
1. check existing container docker with `docker ps -a`
2. if any docker container with name php83dev, execute bash and cd to project directory
3. run php artisan or other php commands to test the code
4. use this if realy needed

## Project Architecture

### Key Concepts

### Master Data
- Master data is used to store basic information used in the system, such as employee, project, client, and cost structure data. This master data serves as the main reference in salary calculation and employee management processes.

#### Karyawan (Employee)
- Employees are divided into 2 types: Organic & Outsourcing
- Employees use the users table and roles, with 'karyawan' role for identification
- Employees can have multiple projects, stored in the karyawan_project table with relationship to project

#### SPK (Surat Perintah Kerja)
SPK is a work order contract that determines the relationship between employees and projects:
- **Flow**: Client → Project → SPK → Employee Assignment
- SPK determines employee work period
- Contains contract information and work scope
- Linked to project cost structure

#### Cost Management
Cost structure setup for each project that determines employee salaries:
- **Gaji Pokok** (Base Salary)
- **BPJS Ketenagakerjaan** (Employment Insurance): 4.89%-6.89% company, 2%-0% employee
- **BPJS Kesehatan** (Health Insurance): 4% company, 1% employee
- **Jaminan Pensiun** (Pension): 2% company, 1% employee
- **Tunjangan Tetap dan Tidak Tetap** (Fixed and Variable Allowances)

#### Payroll Run Process
Process for executing salary calculations based on SPK and cost:
1. Setup salary rules (period, client/project, payment date)
2. Fetch active employees based on SPK
3. Fetch attendance and overtime data
4. Calculate salary based on project cost
5. Apply collective allowances/deductions
6. Calculate PPH 21 (Income Tax)
7. Generate pay slips and exports

#### Collective Adjustments
Allowances/deductions that apply to employee groups:
- **Per Project**: Applies to employees in specific project
- **Per Client**: Applies to employees in specific client
- **Global**: Applies to all employees

### Database Structure

#### Core Tables
- `users` - User authentication and roles
- `roles` - User roles (admin, karyawan, etc)
- `spk` - Surat Perintah Kerja (Work Orders)
- `project` - Project data
- `client` - Client data
- `karyawan_project` - Employee project history (SPK assignments)
- `cost` - Cost structure per project
- `gaji` - Payroll run rules and results
- `tambahan` - Master allowance data
- `potongan` - Master deduction data
- `tambahan_karyawan` - Employee allowances
- `potongan_karyawan` - Employee deductions
- `absences` - Attendance data
- `master_lembur` - Overtime rules
- `lembur_karyawan_project` - Overtime data

#### Table Relationships
- see .github/relationship.md for detailed model relationships


### Workflows
- Stored in `.github/workflow.md` for quick reference
- If needed, update existing workflows or add new workflows as required, using short and clear language. No need for excessive detail, just a general overview.

## File Structure to Follow

```
app/
  ├── Http/Controllers/     # HTTP request handlers
  ├── Models/               # Eloquent models
  ├── Services/             # Business logic layer
  ├── Repositories/         # Data access layer
  ├── Custom/               # Custom helper classes
  ├── Datatables/           # DataTables integration
  └── Export/               # Export functionality

```