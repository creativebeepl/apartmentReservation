export interface Resource {
  id: string
  name: string
}

export interface Booking {
  id: string
  resource_id: string
  resource_name: string
  start_at: string
  end_at: string
  customer_name: string
  created_at: string
}

export interface NewBooking {
  resource_id: string
  start_at: string
  end_at: string
  customer_name: string
}

export interface LoginResult {
  token: string
  expires_at: string
}
