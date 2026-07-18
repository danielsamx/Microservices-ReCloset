<script setup>
/* Consistent icon system for the whole app (Lucide).
   Usage: <Icon name="heart" :size="18" /> */
import {
  Home, Compass, MessageCircle, Bell, Plus, User, LogOut, Menu, X,
  ChevronRight, ChevronLeft, ArrowLeft, Search, SlidersHorizontal,
  Pencil, Trash2, Check, CheckCircle2, AlertTriangle, Info, Eye, EyeOff,
  Send, RotateCw, ImagePlus, Shirt, ShoppingBag, Inbox, Heart, Tag,
  Recycle, Sparkles, MapPin, MessagesSquare, PackageCheck, CircleUser,
} from 'lucide-vue-next'

const map = {
  home: Home, compass: Compass, message: MessageCircle, messages: MessagesSquare,
  bell: Bell, plus: Plus, user: User, 'user-circle': CircleUser, logout: LogOut,
  menu: Menu, close: X, chevronRight: ChevronRight, chevronLeft: ChevronLeft,
  back: ArrowLeft, search: Search, filters: SlidersHorizontal, edit: Pencil,
  trash: Trash2, check: Check, success: CheckCircle2, warning: AlertTriangle,
  info: Info, eye: Eye, 'eye-off': EyeOff, send: Send, retry: RotateCw,
  upload: ImagePlus, shirt: Shirt, bag: ShoppingBag, inbox: Inbox, heart: Heart,
  tag: Tag, recycle: Recycle, sparkles: Sparkles, pin: MapPin, sold: PackageCheck,
}

defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  stroke: { type: [Number, String], default: 2 },
})
</script>
<template>
  <component :is="map[name] || map.info" :size="Number(size)" :stroke-width="Number(stroke)" aria-hidden="true" />
</template>
