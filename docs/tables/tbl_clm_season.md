# Table #clm_saison

| Field            | Type                | Null | Key | Default    | Extra          |
|------------------|---------------------|------|-----|------------|----------------|
| id               | int(11)             | NO   | PRI | NULL       | auto_increment |
| name             | varchar(100)        | NO   |     |            |                |
| published        | tinyint(1)          | NO   | MUL | 0          |                |
| archiv           | tinyint(1)          | NO   | MUL | 0          |                |
| bemerkungen      | text                | YES  |     | NULL       |                |
| bem_int          | text                | YES  |     | NULL       |                |
| checked_out      | int(11) unsigned    | YES  |     | NULL       |                |
| checked_out_time | datetime            | YES  |     | NULL       |                |
| ordering         | int(11)             | NO   |     | 0          |                |
| datum            | date                | NO   |     | 1970-01-01 |                |
| rating_type      | tinyint(1) unsigned | NO   |     | 0          |                |

## Todo:

### Referential Integrity

Data integrity needs to be enforced by referencing the season table in the following tables:

* [todo](todo)

### Renaming:

#### Table
* table should be renamed to #clm_seasons

#### Columns
* archiv -> archive
* bemerkung -> remark
* bem_int -> internal_remark
* datum -> date
 
### Indexing
* Column `name` should be unique

### Questions
* Use of column `ordering`?

## History


