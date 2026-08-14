This is a [Next.js](https://nextjs.org) project bootstrapped with [`create-next-app`](https://nextjs.org/docs/app/api-reference/cli/create-next-app).

## Getting Started

First, run the development server:

```bash
npm run dev
# or
yarn dev
# or
pnpm dev
# or
bun dev
```

Open [http://localhost:3000](http://localhost:3000) with your browser to see the result.

You can start editing the page by modifying `app/page.tsx`. The page auto-updates as you edit the file.

This project uses [`next/font`](https://nextjs.org/docs/app/building-your-application/optimizing/fonts) to automatically optimize and load [Geist](https://vercel.com/font), a new font family for Vercel.

## Environment Variables

Copy `.env.example` to `.env.local` and set:

| Variable | Description |
|----------|-------------|
| `BACKEND_BASE` | Backend API base URL, e.g. `http://localhost:8000` |
| `NEXT_PUBLIC_BACKEND_URL` | Public backend URL used by the client, e.g. `http://localhost:8000/api` |
| `NEXT_PUBLIC_REVERB_APP_KEY` | Reverb public app key (`task-management-key`) |
| `NEXT_PUBLIC_REVERB_HOST` | Reverb WebSocket host (`127.0.0.1`) |
| `NEXT_PUBLIC_REVERB_PORT` | Reverb WebSocket port (`8080`) |

## Real-time Updates

The dashboard subscribes to the private `tasks` channel via [pusher-js](https://github.com/pusher/pusher-js) and refreshes automatically when tasks are created, updated, or deleted. Channel authorization is proxied through `app/api/broadcasting/auth/route.ts`, which forwards the session JWT to the backend's `POST /broadcasting/auth` endpoint.

Requirements: the backend must be running (`php artisan serve`) together with Reverb (`php artisan reverb:start`).


## Learn More

To learn more about Next.js, take a look at the following resources:

- [Next.js Documentation](https://nextjs.org/docs) - learn about Next.js features and API.
- [Learn Next.js](https://nextjs.org/learn) - an interactive Next.js tutorial.

You can check out [the Next.js GitHub repository](https://github.com/vercel/next.js) - your feedback and contributions are welcome!

## Deploy on Vercel

The easiest way to deploy your Next.js app is to use the [Vercel Platform](https://vercel.com/new?utm_medium=default-template&filter=next.js&utm_source=create-next-app&utm_campaign=create-next-app-readme) from the creators of Next.js.

Check out our [Next.js deployment documentation](https://nextjs.org/docs/app/building-your-application/deploying) for more details.
