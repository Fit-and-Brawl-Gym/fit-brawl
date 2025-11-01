# Feedback Filtering and Voting System

## Overview
The feedback page now includes a comprehensive filtering system that allows users to search, filter, and sort testimonials. It also includes a voting system where users can mark feedback as "Helpful" or "Not Helpful".

## Features

### 1. Search Functionality
- **Real-time search**: Users can search feedback by keywords in the message or username
- **Debounced input**: Search executes 500ms after typing stops to reduce server load
- **Case-insensitive**: Searches match regardless of letter casing

### 2. Plan Filter
Users can filter feedback by membership plan:
- All Plans (default)
- Gladiator
- Brawler
- Champion
- Clash
- Resolution Regular
- Resolution Student

### 3. Sort Options
- **Most Recent**: Shows newest feedback first (default)
- **Most Helpful**: Sorts by the number of helpful votes, then by date

### 4. Voting System
- **Helpful/Not Helpful buttons**: Users can vote on each feedback
- **Vote toggling**: Clicking the same vote button again removes the vote
- **Vote switching**: Users can change their vote from helpful to not helpful and vice versa
- **Login required**: Only logged-in users can vote
- **Real-time updates**: Vote counts update immediately without page refresh
- **Visual feedback**: Active votes are highlighted with the accent color

## Database Schema

### New Columns in `feedback` table:
```sql
helpful_count INT DEFAULT 0
not_helpful_count INT DEFAULT 0
```

### New `feedback_votes` table:
```sql
CREATE TABLE feedback_votes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  feedback_id INT NOT NULL,
  user_id INT NOT NULL,
  vote_type ENUM('helpful', 'not_helpful') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_vote (feedback_id, user_id),
  FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## API Endpoints

### GET `/public/php/feedback.php?api=true`
Fetch feedback with filters:

**Query Parameters:**
- `plan` (string): Filter by membership plan ('all', 'Gladiator', 'Brawler', 'Champion', 'Clash', 'Resolution Regular', 'Resolution Student')
- `sort` (string): Sort order ('recent', 'relevant')
- `search` (string): Search keyword

**Response:**
```json
[
  {
    "id": 1,
    "user_id": 5,
    "username": "John Doe",
    "message": "Great gym experience!",
    "avatar": "avatar.jpg",
    "date": "2025-11-01 10:30:00",
    "plan_name": "Gladiator",
    "helpful_count": 15,
    "not_helpful_count": 2,
    "user_vote": "helpful"
  }
]
```

### POST `/public/php/api/feedback_vote.php`
Record or update a vote:

**Request Body:**
```json
{
  "feedback_id": 1,
  "vote_type": "helpful" | "not_helpful" | "remove"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Vote recorded",
  "helpful_count": 16,
  "not_helpful_count": 2,
  "user_vote": "helpful"
}
```

## Installation

### 1. Run Database Migration
Execute the SQL migration file:
```bash
mysql -u username -p database_name < docs/database/feedback_votes_migration.sql
```

### 2. Clear Browser Cache
After deployment, users should clear their cache to load the new CSS and JavaScript.

## UI/UX Design

### Filter Section
- **Dark theme**: Matches the overall site design with dark blue and gold accents
- **Responsive layout**: Filters stack vertically on mobile devices
- **Glassmorphism effect**: Backdrop blur for modern visual appeal
- **Icon integration**: Font Awesome icons for better visual communication

### Feedback Cards
- **Plan badge**: Shows the user's membership plan with a crown icon
- **Vote buttons**: Thumbs up/down with vote counts
- **Active state**: Highlighted when user has voted
- **Date display**: Shows when feedback was submitted
- **Alternating layout**: Left/right pattern for visual interest

### States
- **Loading state**: Spinner animation while fetching data
- **Empty state**: Friendly message when no results match filters
- **Error state**: Clear error message if loading fails

## Security Considerations

1. **SQL Injection Prevention**: All database queries use prepared statements
2. **XSS Protection**: User input is sanitized and HTML-encoded
3. **Authentication Required**: Voting requires active session
4. **One Vote Per User**: Database constraint prevents multiple votes
5. **Transaction Safety**: Vote operations use database transactions

## Performance Optimizations

1. **Debounced Search**: Reduces API calls during typing
2. **Indexed Columns**: Database indexes on helpful_count and date
3. **Lazy Loading**: Could be implemented for large datasets
4. **Cached Results**: Frontend caches filter results

## Browser Compatibility

- **Modern browsers**: Chrome, Firefox, Safari, Edge (latest 2 versions)
- **Vendor prefixes**: Included for backdrop-filter
- **Fallbacks**: Graceful degradation for older browsers

## Future Enhancements

Potential improvements:
- Pagination for large feedback datasets
- Report inappropriate feedback
- Admin moderation panel
- Email notifications for feedback authors when their feedback receives votes
- Export feedback to CSV
- Sentiment analysis of feedback
- Filter by date range
- Filter by rating (if rating system is added)

## Troubleshooting

### Votes Not Saving
- Check if user is logged in
- Verify `feedback_votes` table exists
- Check database foreign key constraints

### Filters Not Working
- Clear browser cache
- Check JavaScript console for errors
- Verify API endpoint is accessible

### Styling Issues
- Ensure CSS file is loaded (check version parameter)
- Clear browser cache
- Check for CSS conflicts with other pages

## Testing Checklist

- [ ] Search functionality works with various keywords
- [ ] Plan filter shows correct results for each plan
- [ ] Sort by "Most Recent" shows newest first
- [ ] Sort by "Most Helpful" shows highest voted first
- [ ] Voting requires login
- [ ] Vote counts update correctly
- [ ] Vote toggling works (remove vote)
- [ ] Vote switching works (change vote)
- [ ] Responsive design works on mobile
- [ ] Empty state displays correctly
- [ ] Loading state displays correctly
- [ ] Error handling works for API failures
