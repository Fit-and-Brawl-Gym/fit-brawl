# Database Files

This folder contains the database schema for the Fit & Brawl Gym Management System.

## Important Files

- **schema.sql** - Complete database structure (tables, indexes, relationships)
  - Use this file for initial setup
  - Import via phpMyAdmin or MySQL command line

## Setup Instructions

1. Create a new database named `fit_and_brawl_gym`
2. Import `schema.sql` to create all tables
3. The database will be empty (no sample data)

## Database Structure Overview

The system includes tables for:
- **Users** - Members, trainers, and admins
- **Memberships** - Subscription plans and user memberships
- **Trainers** - Trainer profiles and schedules
- **Bookings** - Training session reservations
- **Equipment** - Gym equipment inventory
- **Products** - Store items
- **Logs** - Activity tracking and security events
- **And more...**

## Notes for Cleanup

When preparing for demonstration, remove these backup/seed files from the root folder:
- `backup_*.sql` - Old database backups
- `seed_*.sql` - Test data files
- `fix_*.sql` - Database migration scripts
- `update_*.sql` - Schema update scripts
- `test-*.sql` - Testing queries

These files should not be in the repository for a clean setup demonstration.

## First Admin Account

After importing the schema, you'll need to manually create an admin account via phpMyAdmin.
See SETUP.md for detailed instructions.
