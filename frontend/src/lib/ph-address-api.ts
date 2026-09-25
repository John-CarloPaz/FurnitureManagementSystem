import { api } from './api'

export interface PhOption {
  code: string
  name: string
}

export async function fetchProvinces(): Promise<PhOption[]> {
  return (await api.get('/ph/provinces')).data.data
}

export async function fetchCities(province: string): Promise<PhOption[]> {
  return (await api.get(`/ph/provinces/${province}/cities`)).data.data
}

export async function fetchBarangays(city: string): Promise<PhOption[]> {
  return (await api.get(`/ph/cities/${city}/barangays`)).data.data
}
