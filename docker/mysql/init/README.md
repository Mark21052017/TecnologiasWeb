# MySQL local backup

Place the exported `testdb` dump here as `00-testdb.sql` before the first `docker compose up`.

The dump is intentionally ignored by Git because it contains application data. The MySQL entrypoint executes it only when the `mysql_data` volume is empty.
