# DevCord Devmarkt

## setup dev environment
- copy example.dev.env to dev.env
- enter all properties
- add the bot to your server
- run `docker compose -f compose.debug.yaml up`

## Impressum
To provide an impressum, you can either:

1. **Use the default template**: Fill in the following environment variables in your `.env` file:
   - `IMPRESSUM_NAME`
   - `IMPRESSUM_STREET`
   - `IMPRESSUM_CITY`
   - `IMPRESSUM_EMAIL`

2. **Mount a custom file**: Create an `impressum.html` file in the `public` directory or mount it via docker-compose:
```yaml
volumes:
  - ./impressum.html:/var/www/public/impressum.html
```
If `public/impressum.html` exists (either in the image or mounted), it will be used instead of the template.
